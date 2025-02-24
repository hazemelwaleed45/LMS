<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Lecture;

class LectureUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:3|max:255',
            'description' => 'sometimes|string|min:3|max:500',
            'lecture_attachments' => 'nullable|array',
            'lecture_attachments.*.attachment_id' => 'required|string',
            'lecture_attachments.*.name' => 'required|string',
            'lecture_attachments.*.file_extension' => 'required|string|in:.pdf,.docx,.mp4',
            'lecture_attachments.*.size' => 'required|string',
            'duration' => 'sometimes|integer|min:1',
            'course_id' => 'sometimes|exists:courses,id',
        ];
    }

    /**
     * Update the specified lecture.
     */
    public function updateLecture(Lecture $lecture)
    {
        return DB::transaction(function () use ($lecture) {
            $filePath = $lecture->file;
            if ($this->hasFile('file')) {
                $filePath = $this->file('file')->store('course_lecture', 'public');
            }
            $lecture->update([
                'title' => $this->exists('title') ? $this->title : $lecture->title,
                'description' => $this->exists('description') ? $this->description : $lecture->description,
                'duration' => $this->exists('duration') ? $this->duration : $lecture->duration,
                'course_id' => $this->exists('course_id') ? $this->course_id : $lecture->course_id,
            ]);
            return $lecture->refresh();
        });
    }
}
