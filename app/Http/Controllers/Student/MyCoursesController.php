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
}
