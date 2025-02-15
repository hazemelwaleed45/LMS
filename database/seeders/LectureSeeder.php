<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lecture;
use Illuminate\Database\Seeder;

class LectureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Get all courses (assuming you have 12 courses)
        $courses = Course::all();

        // Create 10 lectures for each course
        foreach ($courses as $course) {
            Lecture::factory()->count(10)->create([
                'course_id' => $course->id,
            ]);
        }
    }
}
