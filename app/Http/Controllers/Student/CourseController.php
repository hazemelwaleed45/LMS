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
use App\Models\Lecture;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    public function myCourses(Request $request)
    {
        $user = $request->user();
        $student = Student::with('user')->where('user_id', $user->id)->first();
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }
        $courses = $student->courses;
        return response()->json(CourseResource::collection($courses), 200);
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
        $user = Auth::guard('sanctum')->user();
        $student = $user ? $user->student : null; // If logged in, get the student profile
        $courseName = $request->input('courseName');
        $categoryId = $request->input('categoryId');
        $instructorName = $request->input('instructorName');

        $query = Course::with(['category:id,name', 'instructor:id,name'])
            ->select('id', 'title', 'image', 'category_id', 'instructor_id', 'start_date', 'end_date', 'duration');

        if ($courseName) {
            $query->where('title', 'LIKE', "%{$courseName}%");
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($instructorName) {
            $query->whereHas('instructor', function ($query) use ($instructorName) {
                $query->where('name', 'LIKE', "%{$instructorName}%");
            });
        }

        $courses = $query->get();

        $data = $courses->map(function ($course) use ($student) {
            $canPurchase = true;
            $subscriptionActive = false;

            if ($student) {
                // If the user is logged in, check enrollment
                $enrollment = $student->enrollments()->where('course_id', $course->id)->first();
                log::info('enrollment : ' . $enrollment);
                if ($enrollment) {
                    $enrollmentDate = $enrollment->created_at;
                    $courseEndDate = $enrollmentDate->copy()->addDays($course->duration);

                    if (now()->lessThan($courseEndDate)) {
                        $subscriptionActive = true;
                        $canPurchase = false;
                    }
                }
            }

            return [
                'id' => $course->id,
                'title' => $course->title,
                'image' => $course->image ? asset('storage/app/public/images/courses/' . $course->image) : null,
                'category_name' => $course->category ? $course->category->name : null,
                'instructor_name' => $course->instructor ? $course->instructor->name : null,
                'subscriptionActive' => $subscriptionActive, // If user is logged in, do they have access?
                'canPurchase' => $canPurchase, // Can they buy it?
            ];
        });

        return response()->json(['data' => $data], 200);
    }


    public function getCourse($id)
    {
        // fetch the course from the database and cache the course
        $course = Course::with([
            'category:id,name',
            'instructor:id,name,about,image',
            'lectures:id,course_id,title',
            'reviews.student:id,first_name,last_name,image'
        ])->find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        // Calculate course rating metrics
        $courseRating = $course->getRatingMetrics();
        // Format student feedbacks
        $studentsFeedbacks = $course->reviews->map(function ($review) {
            return [
                'student' => [
                    'id' => $review->student->id,
                    'name' => $review->student->first_name . ' ' . $review->student->last_name,
                    'image' => $review->student->image ? asset('storage/app/public/images/students/' . $review->student->image) : null,
                ],
                'rate' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at->toIso8601String(), // Format as ISO 8601
            ];
        });


        $data =  [
            'id' => $course->id,
            'title' => $course->title,
            'image' => $course->image ? asset('storage/app/public/images/courses/' . $course->image) : null,
            'description' => $course->description ?? null,
            'course_duration' => $course->duration . ' days',
            'course_price' => $course->price,
            'course_level' => $course->course_level,
            //calc students enrroll
            'students_enrolled' => $course->enrollments->count(),
            'instructor' => $course->instructor ? [
                'id' => $course->instructor->id,
                'name' => $course->instructor->name,
                'about' => $course->instructor->about,
                'image' => $course->instructor->image ? asset('storage/app/public/images/instructors/' . $course->instructor->image) : null,
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
            }),
            'course_rating' => $courseRating,
            'students_feedbacks' => $studentsFeedbacks,
        ];

        return response()->json(['data' => $data], 200);
    }

    public function getRelatedCourses($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        // Get related courses (courses in the same category, excluding the current course)
        $relatedCourses = Course::where('category_id', $course->category_id)
            ->where('id', '!=', $course->id) // Exclude the current course
            ->limit(5) // Limit the number of related courses
            ->get(['id', 'title', 'category_id', 'price', 'rate']);

        // Format the related courses
        $formattedRelatedCourses = $relatedCourses->map(function ($relatedCourse) {
            return [
                'id' => $relatedCourse->id,
                'title' => $relatedCourse->title,
                'category' => $relatedCourse->category->name, // Assuming category relationship exists
                'price' => number_format($relatedCourse->price, 2),
                'rating' => round($relatedCourse->rate, 1), // Round to 1 decimal place
                'students_enrolled' => $relatedCourse->enrollments->count(),
            ];
        });

        return response()->json([
            'related_courses' => $formattedRelatedCourses,
        ], 200);
    }
    public function getCourseFeedback($courseId)
    {
        $course = Course::with('reviews.student:id,first_name,last_name,image')->find($courseId);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $reviews = $course->reviews->map(function ($review) {
            return [
                'student_name' => $review->student->first_name . ' ' . $review->student->last_name,
                'image' => $review->student->image ? asset('storage/app/public/images/students/' . $review->student->image) : null,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at->toDateTimeString(),
            ];
        });

        return response()->json(['data' => $reviews], 200);
    }

    public function submitCourseFeedback(Request $request, $courseId)
    {
        $validatedData = $request->validate([
            'student_id' => 'required|exists:students,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $course = Course::find($courseId);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        // Create and save the review
        $review = new Review();
        $review->course_id = $courseId;
        $review->student_id = $validatedData['student_id'];
        $review->rating = $validatedData['rating'];
        $review->comment = $validatedData['comment'];
        $review->review_date = now();
        $review->save();
        $averageRating = Review::where('course_id', $courseId)->avg('rating');
        $course->rate = round($averageRating, 1);
        $course->save();

        return response()->json(['message' => 'Course review submitted successfully'], 201);
    }

    public function getPopularCourses(Request $request)
    {
        // Fetch popular courses based on enrollments & ratings
        $popularCourses = Course::withCount('enrollments')
            ->with('reviews')
            ->orderByDesc('enrollments_count') // Sort by highest enrollments
            ->orderByDesc('rate') // Then by rating
            ->take(4) // Get top 4 popular courses
            ->get();

        // Format the response
        $courses = $popularCourses->map(function ($course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'image' => $course->image ? asset('storage/app/public/images/courses/' . $course->image) : null,
                'category_name' => $course->category ? $course->category->name : null,
                'instructor_name' => $course->instructor ? $course->instructor->name : null,
            ];
        });

        return response()->json([
            'popular_courses' => $courses
        ], 200);
    }
    public function getLectureDetails(Request $request)
    {
        $course_id  = $request->route('course_id');
        $lecture_id = $request->route('lecture_id');

        // Fetch the lecture and ensure it belongs to the given course
        $lecture = Lecture::with(['course'])->where('id', $lecture_id)->where('course_id', $course_id)->first();


        if (!$lecture) {
            return response()->json(['error' => 'Lecture not found for this course'], 404);
        }

        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first(); // Fixed query

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Check if the student is enrolled in this course
        if (!$student->courses->pluck('id')->contains($course_id)) {
            return response()->json(['error' => 'You are not enrolled in this course'], 403);
        }

        return response()->json([
            'data' => [
                'id' => $lecture->id,
                'title' => $lecture->title,
                'description' => $lecture->description,
                'duration' => $lecture->duration,
                'video_path' => $lecture->video_path,
                'attachments' => json_decode($lecture->lecture_attachments, true),
                'course' => [
                    'id' => $lecture->course->id,
                    'name' => $lecture->course->title,
                ],
            ]
        ], 200);
    }
}
