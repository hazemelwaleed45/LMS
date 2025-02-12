<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class InstructorController extends Controller
{
    public function index(Request $request)
    {
        $instructorName = $request->input('name');

        $query = Instructor::with([
            'user:id,email',
            'courses:id,title,instructor_id'
        ])->select(['id', 'name', 'phone', 'user_id']);

        if ($instructorName) {
            $query = $query->where('name', 'LIKE', "%{$instructorName}%");
        }
        $instructors = $query->get();
        $data = $instructors->map(function ($instructor) {
            return [
                'id' => $instructor->id,
                'name' => $instructor->name,
                'phone' => $instructor->phone,
                'email' => $instructor->user->email ?? null,
                'courses' => $instructor->courses ? $instructor->courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                    ];
                }) : [],
            ];
        });

        return response()->json(['data' => $data], 200);
    }

    public function show($id)
    {
        $instructor = Instructor::with(['user', 'courses', 'courses.category', 'courses.reviews'])->find($id);
        if (!$instructor) {
            return response()->json(['error' => 'Instructor not found'], 404);
        }
        return response()->json(['data' => new InstructorResource($instructor)], 200);
    }

    public function store(Request $request)
    {
        // use Validator
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|min:11|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'about' => 'required|string',
            'gender' => 'required|string|in:male,female',
            'date_of_birth' => 'required|date',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
            'paypal_account' => 'required|string|max:255|unique:instructors',
            'major' => 'nullable|string|max:255|in:graduated,university,school',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' =>  Hash::make($request->password),
            'role' => 'instructor'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            // Generate a unique file name
            $imageName = time() . '.' . $request->image->getClientOriginalExtension();
            // Store the image in the public/images/instructors directory
            $request->image->storeAs('images/instructors', $imageName, 'public');
            // Get the full URL of the image
            $imagePath = $imageName;
        }

        $instructor = Instructor::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'about' => $request->about,
            'gender' => $request->gender,
            'user_id' => $user->id,
            'date_of_birth' => $request->date_of_birth,
            'image' => $imagePath,
            'paypal_account' => $request->paypal_account,
            'major' => $request->major,
        ]);

        return response()->json(['data' => new InstructorResource($instructor)], 200);
    }

    public function update(Request $request, $id)
    {
        // Find the instructor by ID
        $instructor = Instructor::find($id);

        if (!$instructor) {
            return response()->json(['error' => 'Instructor not found'], 404);
        }

        // Use Validator to validate the incoming data
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|min:11|max:20',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $instructor->user_id,
            'password' => 'sometimes|string|min:8',
            'about' => 'sometimes|string',
            'gender' => 'sometimes|string|in:male,female',
            'date_of_birth' => 'sometimes|date',
            'image' => 'sometimes|image|mimes:jpg,png,jpeg,gif|max:2048',
            'paypal_account' => 'sometimes|string|max:255|unique:instructors,paypal_account,' . $instructor->id,
            'major' => 'sometimes|string|max:255|in:graduated,university,school',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Update the user if needed
        $user = $instructor->user;
        if ($request->has('email') || $request->has('password')) {
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
        }

        // Delete the old image if a new image is uploaded
        if ($request->hasFile('image')) {
            if ($instructor->image) {
                // Get the correct path to the old image
                // $oldImagePath1 = Storage::disk('public')->path('images/instructors/' . $instructor->image);
                $oldImagePath = storage_path('app/public/images/instructors/' . $instructor->image);
                // Check if the file exists and delete it
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Store the new image
            $imageName = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->storeAs('images/instructors', $imageName, 'public');

            // Save the new image name in the database
            $instructor->image = $imageName;
        }

        $instructor->update([
            'name' => $request->name ?? $instructor->name,
            'phone' => $request->phone ?? $instructor->phone,
            'about' => $request->about ?? $instructor->about,
            'gender' => $request->gender ?? $instructor->gender,
            'date_of_birth' => $request->date_of_birth ?? $instructor->date_of_birth,
            'paypal_account' => $request->paypal_account ?? $instructor->paypal_account,
            'major' => $request->major ?? $instructor->major,
        ]);

        return response()->json(['data' => new InstructorResource($instructor)], 200);
    }

    public function destroy($id)
    {
        // Find the instructor by ID
        $instructor = Instructor::find($id);

        // If the instructor does not exist, return a 404 error
        if (!$instructor) {
            return response()->json(['error' => 'Instructor not found'], 404);
        }

        // Find the associated user
        $user = $instructor->user;

        // Delete the instructor's image if it exists
        if ($instructor->image) {
            $oldImagePath = storage_path('app/public/images/instructors/' . $instructor->image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Delete the instructor record
        $instructor->delete();

        // Delete the associated user record
        if ($user) {
            $user->delete();
        }

        // Return a success response
        return response()->json(['message' => 'Instructor and associated user deleted successfully'], 200);
    }
}
