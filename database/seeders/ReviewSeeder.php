<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Review;
use App\Models\Student;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $courses = Course::all();
        $students = Student::all();

        foreach ($courses as $course) {

            $selectedStudents = $students->random(2);

            foreach ($selectedStudents as $student) {
                Review::factory()->create([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                ]);
            }
        }
    }
}
