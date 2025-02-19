<?php

namespace Database\Factories;

use App\Models\Lecture;
use Illuminate\Database\Eloquent\Factories\Factory;

class LectureFactory extends Factory
{
    protected $model = Lecture::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
            'description' => $this->faker->paragraph,
            'content_url' => $this->faker->url,
            'duration' => $this->faker->numberBetween(30, 120), // Duration in minutes
            'course_id' => $this->faker->numberBetween(1, 12), // Assuming you have 12 courses
            'file' => $this->faker->imageUrl(), // Example file URL
        ];
    }
}
