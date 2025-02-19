<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class MyCoursesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'duration' => $this->duration,
            'start_date' => Carbon::parse($this->start_date)->toDateString(),
            'end_date' => Carbon::parse($this->end_date)->toDateString(),
            'rate' => $this->rate,
            'image' => $this->image,
            'admin_amount' => $this->admin_amount,
            'instructor_amount' => $this->instructor_amount,
            'major' => $this->major,
            'prerequister' => $this->prerequister,
            'semster' => $this->semster,
            'course_level' => $this->course_level,
            'introduction_vedio_path' => $this->introduction_vedio_path,

            //instructor
            'instructor' => $this->instructor ? [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
                'about' => $this->instructor->about,
                'gender' => $this->instructor->gender,
                'date_of_birth' => $this->instructor->date_of_birth,
                'image' => $this->instructor->image,
                'phone' => $this->instructor->phone,
                'paypal_account' => $this->instructor->paypal_account,
                'major' => $this->instructor->major,
            ] : null,

            // lectures
            'lectures' => $this->lectures ? $this->lectures->map(function ($lecture) {
                return [
                    'id' => $lecture->id,
                    'title' => $lecture->title,
                    'content' => $lecture->content,
                    'description' => $lecture->description,
                    'content_url' => $lecture->content_url,
                    'duration' => $lecture->duration,
                    'file' => $lecture->file,
                    'meetings' => $lecture->meetings ? $lecture->meetings->map(function ($meeting) {
                        return [
                            'id' => $meeting->id,
                            'meeting_title' => $meeting->meeting_title,
                            'start_time' => $meeting->start_time,
                            'duration' => $meeting->duration,
                            'meeting_url' => $meeting->meeting_url,
                        ];
                    }) : [],
                    'assignments' => $lecture->assignments ? $lecture->assignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'title' => $assignment->title,
                            'grade' => $assignment->grade,
                            'duration' => $assignment->duration,
                            'content' => $assignment->content,
                            'exams' => $assignment->exams ? $assignment->exams->map(function ($exam) {
                                return [
                                    'id' => $exam->id,
                                    'date' => $exam->date,
                                    'grade' => $exam->grade,
                                ];
                            }) : [],
                        ];
                    }) : [],
                ];
            }) : [],

            // reviews
            'reviews' => $this->reviews ? $this->reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review_date' => $review->review_date,
                    'comment' => $review->comment,
                    'student' => $review->student ? [
                        'id' => $review->student->id,
                        'name' => $review->student->first_name . ' ' . $review->student->last_name,
                        
                        'image' => $review->student->image,
                    ] : null,
                ];
            }) : [],
        ];
    }
}