<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class PageController extends Controller
{
    public function show($pageName)
    {
        $content = Page::where('page_name', $pageName)->get(['key', 'value', 'json_value']);
    
        // Decode the json_value for each item
        $decodedContent = $content->map(function ($item) {
            $item->json_value = json_decode($item->json_value); // Decode JSON
            return $item;
        });
    
        return response()->json(['data' => $decodedContent], 200);
    }

    public function update(Request $request, $pageName)
    {
        // Define validation rules
        $rules = [
            'key' => 'nullable|string|max:255', // Ensure each key is a string
            'value' => 'nullable|string',       // Allow simple text or HTML
            'json_value' => 'nullable',         // Allow any type of input for now
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Convert json_value to JSON string if it's an array
        $data = $request->all();
        if (is_array($data['json_value'])) {
            $data['json_value'] = json_encode($data['json_value']);
        }

        // Find the record to update
        $page = Page::where('page_name', $pageName)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // Filter out null fields to only update provided values
        $updateData = array_filter([
            'key' => $data['key'] ?? null,
            'value' => $data['value'] ?? null,
            'json_value' => $data['json_value'] ?? null,
        ], fn($value) => $value !== null);

        // Update the existing record if there is any data to update
        if (!empty($updateData)) {
            $page->update($updateData);
        }

        return response()->json(['message' => 'Page updated successfully', 'page' => $page], 200);
    }

    public function uploadImage(Request $request, $pageName)
    {
        Log::info('Request Data:', $request->all());
    
        // ✅ Step 1: Validate Request Data
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'key' => 'required|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // ✅ Step 2: Find the Page Entry in the Database
        $pageEntry = Page::where('page_name', $pageName)
                         ->where('key', $request->input('key'))
                         ->first();
    
        if (!$pageEntry) {
            return response()->json(['message' => 'Page or key not found.'], 404);
        }
    
        DB::beginTransaction();
        try {
            // ✅ Step 3: Delete the Old Image if It Exists
            if (!empty($pageEntry->value)) {
                $oldFilePath = str_replace(asset('storage/') . '/', '', $pageEntry->value);
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                    Log::info('Old image deleted:', ['path' => $oldFilePath]);
                }
            }
    
            // ✅ Step 4: Store the New Image
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $request->file('image')->getClientOriginalName());
            $path = $request->file('image')->storeAs('uploads', $fileName, 'public');
            $url = asset('storage/' . $path);
    
            // ✅ Step 5: Save Image URL in Database
            $pageEntry->value = $url;
            $pageEntry->json_value = null; // Reset JSON field if needed
            $pageEntry->save();
    
            DB::commit();
    
            return response()->json([
                'message' => 'Image uploaded successfully.',
                'url' => $url,
                'data' => $pageEntry,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Upload failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Image upload failed.'], 500);
        }
    }
    
    public function appendToPage(Request $request, $pageName)
    {
        Log::info('Request Data:', $request->all());

        // ✅ Step 1: Validate Request
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'append_value' => 'required', // Accepts both string and array
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ✅ Step 2: Find the Page Entry
        $pageEntry = Page::where('page_name', $pageName)
                        ->where('key', $request->input('key'))
                        ->first();

        if (!$pageEntry) {
            return response()->json(['message' => 'Key not found for the given page.'], 404);
        }

        DB::beginTransaction();
        try {
            // ✅ Step 3: Handle `value` Column (String)
            if (!empty($pageEntry->value)) {
                if (!is_array($request->input('append_value'))) {
                    // Append text to existing value
                    $pageEntry->value .= ' ' . $request->input('append_value');
                } else {
                    return response()->json(['message' => 'Cannot append array data to a string value.'], 400);
                }
            } 
            // ✅ Step 4: Handle `json_value` Column (JSON Array)
            elseif (!empty($pageEntry->json_value)) {
                $jsonData = json_decode($pageEntry->json_value, true);

                if (!is_array($jsonData)) {
                    return response()->json(['message' => 'Cannot append to non-array JSON content.'], 400);
                }

                $jsonData[] = $request->input('append_value');
                $pageEntry->json_value = json_encode($jsonData, JSON_PRETTY_PRINT);
            } 
            // ✅ Step 5: If Both `value` and `json_value` Are Empty, Save as a String
            else {
                if (is_array($request->input('append_value'))) {
                    $pageEntry->json_value = json_encode([$request->input('append_value')], JSON_PRETTY_PRINT);
                } else {
                    $pageEntry->value = $request->input('append_value');
                }
            }

            // ✅ Step 6: Save Changes
            $pageEntry->save();
            DB::commit();

            return response()->json([
                'message' => 'Content appended successfully.',
                'data' => [
                    'value' => $pageEntry->value,
                    'json_value' => json_decode($pageEntry->json_value, true)
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Append failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Append operation failed.'], 500);
        }
    }

}
