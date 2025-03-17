<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboardStudent()
    {
        $student = Auth::user()->student; // Get the logged-in student

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Count enrolled, active, and completed courses
        $totalEnrolledCourses = $student->enrollments()->count();
        $activeCourses = $student->courses()->where('end_date', '>', now())->count();
        $completedCourses = $student->courses()->where('end_date', '<', now())->count();

        // Get unique enrolled category IDs
        $enrolledCategoryIds = $student->courses()->pluck('courses.category_id')->unique();

        // Get enrolled course IDs explicitly using "courses.id"
        $enrolledCourseIds = $student->courses()->select('courses.id')->pluck('id');

        // Get recommended courses from the same categories but exclude enrolled courses
        $recommendedCourses = Course::whereIn('category_id', $enrolledCategoryIds)
            ->whereNotIn('id', $enrolledCourseIds) // Avoid ambiguity
            ->limit(4)
            ->get(['id', 'title', 'image', 'category_id']);

        // If less than 4 recommended courses, get random ones
        if ($recommendedCourses->count() < 4) {
            $moreCourses = Course::whereNotIn('id', $enrolledCourseIds)
                ->inRandomOrder()
                ->limit(4 - $recommendedCourses->count())
                ->get(['id', 'title', 'image', 'category_id']);

            $recommendedCourses = $recommendedCourses->merge($moreCourses);
        }

        // Student info
        $studentInfo = [
            'name' => $student->first_name . ' ' . $student->last_name,
            'image' => asset('storage/app/public/images/students/' . $student->image),
        ];

        return response()->json([
            'student' => $studentInfo,
            'totalEnrolledCourses' => $totalEnrolledCourses,
            'activeCourses' => $activeCourses,
            'completedCourses' => $completedCourses,
            'totalCategories' => $enrolledCategoryIds->count(),
            'recommendedCourses' => $recommendedCourses
        ]);
    }

}
