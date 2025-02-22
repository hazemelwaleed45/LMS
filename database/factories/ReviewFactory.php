<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition()
    {
        return [
            'rating' => $this->faker->numberBetween(1, 5), // Random rating between 1 and 5
            'review_date' => $this->faker->dateTimeBetween('-1 year', 'now'), // Random review date within the past year
            'course_id' => $this->faker->numberBetween(1, 12), // Assuming you have 12 courses
            'student_id' => $this->faker->numberBetween(1,3), // Assuming you have 50 students
            'comment' => $this->faker->paragraph, // Random comment
        ];
    }
}
