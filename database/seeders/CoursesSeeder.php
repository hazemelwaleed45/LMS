<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoursesSeeder extends Seeder
{
    public function run()
    {
        $courses = [
            // Courses for Instructor 1: Omar Sehly
            [
                'title' => 'Advanced Mathematics',
                'description' => 'Learn advanced mathematical concepts and problem-solving.',
                'price' => 200,
                'duration' => 30,
                'start_date' => '2025-01-15',
                'end_date' => '2025-02-15',
                'rate' => 4.8,
                'image' => 'math_course_1.jpg',
                'admin_amount' => 50,
                'instructor_amount' => 150,
                'category_id' => 1, // Mathematics
                'instructor_id' => 1, // Omar Sehly
                'major' => 'Mathematics',
                'prerequisite' => 'Basic Mathematics',
                'semester' => 'semester1',
                "course_level" => "advanced",
                "introduction_vedio_path" =>"storage/videos/laravel-advanced.mp4"
            ],
            [
                'title' => 'Calculus for Beginners',
                'description' => 'An introduction to calculus concepts and applications.',
                'price' => 150,
                'duration' => 20,
                'start_date' => '2025-02-01',
                'end_date' => '2025-02-20',
                'rate' => 4.5,
                'image' => 'math_course_2.jpg',
                'admin_amount' => 40,
                'instructor_amount' => 110,
                'category_id' => 1, // Mathematics
                'instructor_id' => 1, // Omar Sehly
                'major' => 'Mathematics',
                'prerequisite' => 'None',
                'semester' => 'semester2',
                "course_level" => "beginners",
                "introduction_vedio_path" =>"storage/videos/laravel-beginners.mp4"
            ],
            [
                'title' => 'Linear Algebra Mastery',
                'description' => 'Understand and apply linear algebra in various fields.',
                'price' => 180,
                'duration' => 25,
                'start_date' => '2025-03-01',
                'end_date' => '2025-03-25',
                'rate' => 4.7,
                'image' => 'math_course_3.jpg',
                'admin_amount' => 45,
                'instructor_amount' => 135,
                'category_id' => 1, // Mathematics
                'instructor_id' => 1, // Omar Sehly
                'major' => 'Mathematics',
                'prerequisite' => 'Basic Algebra',
                'semester' => 'semester2',
                "course_level" => "intermediate",
                "introduction_vedio_path" =>"storage/videos/laravel-intermediate.mp4"
            ],

            // Courses for Instructor 2: Mohamed Ibrahim
            [
                'title' => 'Physics for Engineers',
                'description' => 'Essential physics concepts for engineering students.',
                'price' => 220,
                'duration' => 35,
                'start_date' => '2025-01-20',
                'end_date' => '2025-02-25',
                'rate' => 4.9,
                'image' => 'physics_course_1.jpg',
                'admin_amount' => 60,
                'instructor_amount' => 160,
                'category_id' => 2, // Physics
                'instructor_id' => 2, // Mohamed Ibrahim
                'major' => 'Physics',
                'prerequisite' => 'Basic Physics',
                "course_level" => "intermediate",
                "introduction_vedio_path" =>"storage/videos/laravel-intermediate0.mp4"
            ],
            [
                'title' => 'Quantum Mechanics Basics',
                'description' => 'Introduction to quantum mechanics and its applications.',
                'price' => 300,
                'duration' => 40,
                'start_date' => '2025-03-01',
                'end_date' => '2025-04-01',
                'rate' => 4.6,
                'image' => 'physics_course_2.jpg',
                'admin_amount' => 70,
                'instructor_amount' => 230,
                'category_id' => 2, // Physics
                'instructor_id' => 2, // Mohamed Ibrahim
                'major' => 'Physics',
                'prerequisite' => 'None',
                'semester' => 'semester1',
                "course_level" => "beginners",
                "introduction_vedio_path" =>"storage/videos/laravel-beginners0.mp4"
            ],
            [
                'title' => 'Thermodynamics Essentials',
                'description' => 'A complete guide to thermodynamics for students.',
                'price' => 180,
                'duration' => 15,
                'start_date' => '2025-04-01',
                'end_date' => '2025-04-30',
                'rate' => 4.8,
                'image' => 'physics_course_3.jpg',
                'admin_amount' => 50,
                'instructor_amount' => 130,
                'category_id' => 2, // Physics
                'instructor_id' => 2, // Mohamed Ibrahim
                'major' => 'Physics',
                'prerequisite' => 'Basic Thermodynamics',
                'semester' => 'semester2',
                "course_level" => "advanced",
                "introduction_vedio_path" =>"storage/videos/laravel-advanced0.mp4"
            ],
        ];

        // Insert courses
        foreach ($courses as $course) {
            DB::table('courses')->insert(array_merge($course, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}