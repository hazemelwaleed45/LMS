<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
class CourseStoreRequest extends FormRequest
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
            'title' => 'required|string|min:3|max:255|unique:courses,title',
            'description' => 'required|string|min:3|max:500',
            'price' => 'required|numeric|min:0|max:9999999.99',
            'duration' => 'required|integer|min:1',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'rate' => 'nullable|numeric|min:0|max:5',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'admin_amount' => 'required|numeric|min:0|max:9999999.99',
            'instructor_amount' => 'required|numeric|min:0|max:9999999.99',
            'category_id' => 'required|exists:categories,id',
            'instructor_id' => 'required|exists:instructors,id',
            'major' => 'nullable|string|max:255',
            'prerequister' => 'nullable|string|max:255',
            'semster' => 'required|in:semster1,semster2',
        ];
    }
    public function storeCourse()
    {
        return DB::transaction(function () {
            $imagePath = null;
                if ($this->hasFile('image')) {
                    $imagePath = $this->file('image')->store('course_images', 'public');
                }
            $course = Course::create([
                'title' => $this->title,
                'description' => $this->description,
                'price' => $this->price,
                'duration' => $this->duration,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'rate' => $this->rate,
                'image' => $imagePath ?? null,
                'admin_amount' => $this->admin_amount,
                'instructor_amount' => $this->instructor_amount,
                'category_id' => $this->category_id,
                'instructor_id' => $this->instructor_id,
                'major' => $this->major,
                'prerequister' => $this->prerequister,
                'semster' => $this->semster,
            ]);
            return $course->refresh();
        });
    }
}