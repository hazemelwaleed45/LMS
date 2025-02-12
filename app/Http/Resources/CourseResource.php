<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CourseResource extends JsonResource
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
            'category_id' => $this->category_id,
            'instructor_id' => $this->instructor_id,
            'major' => $this->major,
            'prerequister' => $this->prerequister,
            'semster' => $this->semster,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}