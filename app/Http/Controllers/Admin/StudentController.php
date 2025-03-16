<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{

    //
    public function index(Request $request)
    {
        try {
            $query = Student::with([
                'user:id,email',
                'payments:id,amount,student_id',
                // 'meetings:id,title,student_id',
                // 'exams:id,title,student_id'
            ])->select(['id', 'first_name', 'last_name', 'username', 'date_of_birth', 'image', 'education', 'gender', 'country', 'phone', 'user_id']);

            $students = $query->get();

            $data = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'education' => $student->education,
                    'phone' => $student->phone,
                    'email' => $student->user->email ?? null,
                ];
            });

            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching student data.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function show($id)
    {
        // Fetch the student with their related data
        $student = Student::with([
            'user:id,email', // Fetch only the email from the user relationship
            'courses:id,title', // Fetch only the course ID and title
        ])->find($id);

        // Check if the student exists
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Format the response data
        $data = [
            'id' => $student->id,
            'name' => $student->first_name . ' ' . $student->last_name, // Combine first and last name
            'email' => $student->user->email ?? null, // Get email from the user relationship
            'gender' => $student->gender,
            'education' => $student->education,
            'phone' => $student->phone,
            'country' => $student->country,
            'enrolled_courses' => $student->courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                ];
            }),
        ];

        // Return the response
        return response()->json(['data' => $data], 200);
    }

    public function store(Request $request)
    {
        // Use Validator
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:students',
            'phone' => 'required|string|min:11|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'date_of_birth' => 'required|date',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
            'education' => 'required|string|max:255',
            'gender' => 'required|string|in:male,female',
            'country' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Create the user
        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name, // Combine first and last name
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student', // Set role to 'student'
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            // Generate a unique file name
            $imageName = time() . '.' . $request->image->getClientOriginalExtension();
            // Store the image in the public/images/students directory
            $request->image->storeAs('images/students', $imageName, 'public');
            // Get the full URL of the image
            $imagePath = $imageName;
        }

        // Create the student
        $student = Student::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'image' => $imagePath,
            'education' => $request->education,
            'gender' => $request->gender,
            'country' => $request->country,
            'user_id' => $user->id, // Link the student to the user
        ]);

        return response()->json(['data' => $student], 200);
    }

    public function update(Request $request, $id)
    {
        // Find the student by ID
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Use Validator to validate the incoming data
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:students,username,' . $student->id,
            'phone' => 'sometimes|string|min:11|max:20',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $student->user_id,
            'password' => 'sometimes|string|min:8',
            'date_of_birth' => 'sometimes|date',
            'image' => 'sometimes|image|mimes:jpg,png,jpeg,gif|max:2048',
            'education' => 'sometimes|string|max:255',
            'gender' => 'sometimes|string|in:male,female',
            'country' => 'sometimes|string|max:255',
            'interests' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        // Ensure only the admin or the student (owner) can update the profile
        $user = auth()->user();
        if ($user->role !== 'admin' && $user->id !== $student->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        // Update the user if needed
       $user = $student->user;
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
            if ($student->image) {
                // Get the correct path to the old image
                $oldImagePath = storage_path('app/public/images/students/' . $student->image);
                // Check if the file exists and delete it
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Store the new image
            $imageName = time() . '.' . $request->image->getClientOriginalExtension();
            $request->image->storeAs('images/students', $imageName, 'public');

            // Save the new image name in the database
            $student->image = $imageName;
        }

        // Update the student
        $student->update([
            'first_name' => $request->first_name ?? $student->first_name,
            'last_name' => $request->last_name ?? $student->last_name,
            'username' => $request->username ?? $student->username,
            'phone' => $request->phone ?? $student->phone,
            'date_of_birth' => $request->date_of_birth ?? $student->date_of_birth,
            'education' => $request->education ?? $student->education,
            'gender' => $request->gender ?? $student->gender,
            'country' => $request->country ?? $student->country,
            'interests' => $request->interests ?? $student->interests,
        ]);

        return response()->json(['data' => $student], 200);
    }

    public function destroy($id)
    {
        // Find the student by ID
        $student = Student::find($id);

        // If the student does not exist, return a 404 error
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Find the associated user
        $user = $student->user;

        // Delete the student's image if it exists
        if ($student->image) {
            $oldImagePath = storage_path('app/public/images/students/' . $student->image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Delete the student record
        $student->delete();

        // Delete the associated user record
        if ($user) {
            $user->delete();
        }

        // Return a success response
        return response()->json(['message' => 'Student and associated user deleted successfully'], 200);
    }
}
