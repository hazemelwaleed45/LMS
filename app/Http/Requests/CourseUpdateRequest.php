<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
class CourseUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this this.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the this.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:3|max:255|unique:courses,title,' . $this->route('course'),
            'description' => 'sometimes|string|min:3|max:500',
            'price' => 'sometimes|numeric|min:0|max:9999999.99',
            'duration' => 'sometimes|integer|min:1',
            'start_date' => 'sometimes|date|before_or_equal:end_date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'rate' => 'sometimes|nullable|numeric|min:0|max:5|regex:/^\d+(\.\d{1})?$/',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'admin_amount' => 'sometimes|numeric|min:0|max:9999999.99',
            'instructor_amount' => 'sometimes|numeric|min:0|max:9999999.99',
            'category_id' => 'sometimes|exists:categories,id',
            'instructor_id' => 'sometimes|exists:instructors,id',
            'major' => 'sometimes|nullable|string|max:255',
            'prerequister' => 'sometimes|nullable|string|max:255',
            'semster' => 'sometimes|required|in:semster1,semster2',
        ];
    }
    public function UpdateCourse(Course $course)
{
    return DB::transaction(function() use ($course){
        $imagePath = $course->image;
        if ($this->hasFile('image')) {
            $imagePath = $this->file('image')->store('course_images', 'public');
        }

        $this->course->update([
            'title' => $this->exists('title') ? $this->title : $this->course('title'),
            'description' => $this->exists('description') ? $this->description : $this->course('description'),
            'price' => $this->exists('price') ? $this->price : $this->course('price'),
            'duration' => $this->exists('duration') ? $this->duration : $this->course('duration'),
            'start_date' => $this->exists('start_date') ? $this->start_date : $this->course('start_date'),
            'end_date' => $this->exists('end_date') ? $this->end_date : $this->course('end_date'),
            'rate' => $this->exists('rate') ? $this->rate : $this->course('rate'),
            'image' => $imagePath,
            'admin_amount' => $this->exists('admin_amount') ? $this->admin_amount : $this->course('admin_amount'),
            'instructor_amount' => $this->exists('instructor_amount') ? $this->instructor_amount : $this->course('instructor_amount'),
            'category_id' => $this->exists('category_id') ? $this->category_id : $this->course('category_id'),
            'instructor_id' => $this->exists('instructor_id') ? $this->instructor_id : $this->course('instructor_id'),
            'major' => $this->exists('major') ? $this->major : $this->course('major'),
            'prerequister' => $this->exists('prerequister') ? $this->prerequister : $this->course('prerequister'),
            'semster' => $this->exists('semster') ? $this->semster : $this->course('semster'),
        ]);
        return $this->course->refresh();
    });
}
}
