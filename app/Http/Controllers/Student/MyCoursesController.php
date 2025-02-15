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
use Illuminate\Support\Facades\Storage;

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

    public function getCourses(Request $request)
    {
        $courseName = $request->input('courseName');
        $categoryId = $request->input('categoryId');
        $instructorName = $request->input('instructorName');

        $query = Course::with(['category:id,name', 'instructor:id,name'])->select('id', 'title', 'image', 'category_id', 'instructor_id');

        if ($courseName) {
            $query = $query->where('title', 'LIKE', "%{$courseName}%");
        }
        if ($categoryId) {
            $query = $query->where('category_id', $categoryId);
        }
        if ($instructorName) {
            $query = $query->whereHas('instructor', function ($query) use ($instructorName) {
                $query->where('name', 'LIKE', "%{$instructorName}%");
            });
        }

        $courses = $query->get();

        $data = $courses->map(function ($course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'image' => $course->image
                    ? Storage::disk('public')->url('images/courses/' . $course->image)
                    : null,
                'category_name' => $course->category ? $course->category->name : null,
                'instructor_name' => $course->instructor ? $course->instructor->name : null,
            ];
        });

        return response()->json(['data' => $data], 200);
    }

    public function getCourse($id)
    {
        // cache the course
        $cacheKey = "course_". $id;
        $cachedData = cache()->get($cacheKey);

        if ($cachedData) {
            return response()->json($cachedData, 200);
        }
        // fetch the course from the database and cache the course
        $course = Course::with(['category:id,name', 'instructor:id,name,about,image', 'lectures:id,course_id,title'])->find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $data =  [
            'id' => $course->id,
            'title' => $course->title,
            'image' => $course->image
                ? Storage::disk('public')->url('images/courses/' . $course->image)
                : null,
            'description' => $course->description ?? null,
            'instructor' => $course->instructor ? [
                'id' => $course->instructor->id,
                'name' => $course->instructor->name,
                'about' => $course->instructor->about,
                'image' => $course->instructor->image
                    ? Storage::disk('public')->url('images/instructors/' . $course->instructor->image)
                    : null,
            ] : null,
            'category' => $course->category ? [
                'id' => $course->category->id,
                'name' => $course->category->name,
                'description' => $course->category->description,
            ] : null,
            'lectures' => $course->lectures->map(function ($lecture) {
                return [
                    'id' => $lecture->id,
                    'title' => $lecture->title
                ];
            })
        ];
        cache()->put($cacheKey, $data, now()->addMinutes(60));
        return response()->json(['data' => $data], 200);
    }
}
