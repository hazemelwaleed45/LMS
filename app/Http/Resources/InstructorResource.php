<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class InstructorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'about' => $this->about,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'image' => $this->image
                ? Storage::disk('public')->url('images/instructors/' . $this->image)
                : null,
            'phone' => $this->phone,
            'paypal_account' => $this->paypal_account,
            'major' => $this->major,
            'email' => $this->user->email,
            'roles' => $this->user->role,
            'courses_count' => $this->courses->count(),
            'courses' => $this->courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'duration' => $course->duration,
                    'price' => $course->price,
                    'start_date' => $course->start_date,
                    'end_date' => $course->end_date,
                    'rate' => $course->rate,
                    // Include the full URL for the course image
                    'image' => $course->image
                        ? Storage::disk('public')->url('images/courses/' . $course->image)
                        : null,
                    'major' => $course->major,
                    'prerequister' => $course->prerequister,
                    'semster' => $course->semster,
                    'category' => $course->category->name,
                    'category_description' => $course->category->description
                ];
            }),

        ];
    }
}