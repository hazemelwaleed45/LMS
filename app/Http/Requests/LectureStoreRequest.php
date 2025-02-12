<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Lecture;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
class LectureStoreRequest extends FormRequest
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
            'title' => 'required|string|min:3|max:255|unique:lectures,title',
            'description' => 'required|string|min:3|max:500',
            'content' => 'nullable|string',
            'content_url' => 'nullable|url',
            'duration' => 'required|integer|min:1',
            'file' => 'nullable|file|mimes:pdf,docx,mp4|max:10240',
            'course_id' => 'required|exists:courses,id',
        ];
    }
    public function storeLecture(Course $course)
    {
        return DB::transaction(function () use ($course) {
            $filePath = null;
            if ($this->hasFile('file')) {
                $filePath = $this->file('file')->store('course_lecture', 'public');
            }
            $lecture = $course->lectures()->create([
                'title' => $this->title,
                'description' => $this->description,
                'content' => $this->content,
                'content_url' => $this->content_url,
                'duration' => $this->duration,
                'file' => $filePath,
            ]);
            return $lecture->refresh();
        });
    }
}