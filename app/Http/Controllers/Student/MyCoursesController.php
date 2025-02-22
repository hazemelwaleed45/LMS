<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CourseResource;
use App\Http\Resources\MyCoursesResource;
use App\Models\Student;
use App\Models\Course;
use App\Models\Review;
use App\Models\Instructor;
use App\Models\Category;
use App\Models\User;

class MyCoursesController extends Controller
{
    //
    public function myCourses(Request $request)
    {

        $user = $request->user();


        $student = Student::with('user')->where('user_id', $user->id)->first();


        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }


        $courses = $student->courses;


        $formattedCourses = CourseResource::collection($courses);


        return response()->json($formattedCourses, 200);
    }

    public function view(Request $request, Course $course)
    {

        $user = $request->user();

        $student = Student::with('user')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        if (!$student->courses->contains($course)) {
            return response()->json(['error' => 'Course not found in your enrolled courses'], 404);
        }

        $course->load([
            'instructor',
            'lectures.meetings',
            'lectures.assignments.exams',
            'reviews.student',
        ]);


        return response()->json(new MyCoursesResource($course), 200);
    }

    public function getLectureDetails(Request $request, Lecture $lecture)
    {
    $user = $request->user();
    $student = Student::where('user_id', $user->id)->first();

    if (!$student) {
        return response()->json(['error' => 'Student not found'], 404);
    }
    if (!$student->courses->contains($lecture->course_id)) {
        return response()->json(['error' => 'You are not enrolled in this course'], 403);
    }

    $lecture->load(['attachments']);

    return response()->json([
        'lecture_title' => $lecture->title,
        'lecture_description' => $lecture->description,
        'lecture_notes' => $lecture->notes,
        'lecture_attachments' => $lecture->attachments->map(function ($attachment) {
            return [
                'attachment_id' => $attachment->id,
                'name' => $attachment->name,
                'file_extension' => $attachment->file_extension,
                'size' => $attachment->size,
            ];
        }),
    ], 200);
    }
    
    public function addReview(Request $request, Course $course)
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        if (!$student->courses->contains($course)) {
            return response()->json(['error' => 'You are not enrolled in this course'], 403);
        }

        $rules = [
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review = Review::updateOrCreate(
            [
                'course_id' => $course->id,
                'student_id' => $student->id,
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
                'review_date' => now(),
            ]
        );

        return response()->json([
            'message' => 'Review added successfully',
            'data' => $review,
        ], 201);
    }

    public function updateReview(Request $request, Course $course)
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        if (!$student->courses->contains($course)) {
            return response()->json(['error' => 'You are not enrolled in this course'], 403);
        }

        $review = Review::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$review) {
            return response()->json(['error' => 'Review not found'], 404);
        }

        $rules = [
            'rating' => 'nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $review->update([
            'rating' => $request->rating ?? $review->rating,
            'comment' => $request->comment ?? $review->comment,
            'review_date' => now(),
        ]);

        return response()->json([
            'message' => 'Review updated successfully',
            'data' => $review,
        ], 200);
    }

    public function deleteReview(Course $course, Request $request)
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        if (!$student->courses->contains($course)) {
            return response()->json(['error' => 'You are not enrolled in this course'], 403);
        }

        $review = Review::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$review) {
            return response()->json(['error' => 'Review not found'], 404);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully',
        ], 200);
    }
}
