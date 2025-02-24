<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LectureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'lecture_id' => $this->id,
            'lecture_title' => $this->title,
            'lecture_description' => $this->description,
            'lecture_notes' => $this->lecture_notes ?? null,
            'lecture_attachments' => json_decode($this->lecture_attachments, true) ?? [],
            'lecture_duration' => $this->duration,
            'course_id' => $this->course_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
