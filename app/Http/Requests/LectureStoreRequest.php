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
            'description' => 'nullable|string|min:3|max:500',
            'lecture_attachments' => 'nullable|array',
            'lecture_attachments.*.name' => 'required|string',
            'lecture_attachments.*.file_extension' => 'required|string|in:pdf,docx,mp4',
            'lecture_attachments.*.size' => 'required|string',
            'file' => 'required|file|mimes:pdf,docx,mp4|max:10240', 
            'duration' => 'required|integer|min:1',
            'course_id' => 'required|exists:courses,id',
        ];
    }

    public function storeLecture(Course $course)
    {
        return DB::transaction(function () use ($course) {
            $filePath = null;
            $fileData = null;

            if ($this->hasFile('file')) {
                $file = $this->file('file');
                $filePath = $file->store('uploads/lectures', 'public');

                $fileData = [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize() . ' bytes',
                    'file_extension' => $file->getClientOriginalExtension(),
                    'path' => $filePath,
                ];
            }

            $lecture = $course->lectures()->create([
                'title' => $this->title,
                'description' => $this->description,
                'lecture_attachments' => $fileData,
                'duration' => $this->duration,
            ]);

            return $lecture->refresh();
        });
    }

}