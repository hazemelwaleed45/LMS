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
            'content' => 'sometimes|nullable|string',
            'content_url' => 'sometimes|nullable|url',
            'duration' => 'sometimes|integer|min:1',
            'file' => 'sometimes|nullable|file|mimes:pdf,docx,mp4|max:10240',
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
                'content' => $this->exists('content') ? $this->content : $lecture->content,
                'content_url' => $this->exists('content_url') ? $this->content_url : $lecture->content_url,
                'duration' => $this->exists('duration') ? $this->duration : $lecture->duration,
                'file' => $filePath,
                'course_id' => $this->exists('course_id') ? $this->course_id : $lecture->course_id,
            ]);
            return $lecture->refresh();
        });
    }
}
