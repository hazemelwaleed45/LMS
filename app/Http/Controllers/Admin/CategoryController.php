<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index ()
    {
        return CategoryResource::collection(Category::all());
    }

    public function show($id)
    {
        $category = Category::with('courses')->find($id);
        if (!$category) {
            return response()->json(['error' => 'Course not found'], 404);
        }
        return new CategoryResource($category);
    }

    public function store (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $category =  Category::create($validator->validated());
        return new CategoryResource($category);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $category->update($validator->validated());

        return new CategoryResource($category);
    }

    public function destroy ($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        $category->delete();
        return response()->json(['message'=>'Category deleted successfully'], 200);
    }

    public function getCourses (Category $category)
    {
        return new CategoryResource($category->load('courses'));
    }
}
