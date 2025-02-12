<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LectureStoreRequest;
use App\Http\Requests\LectureUpdateRequest;
use App\Models\Course;
use App\Models\Lecture;

class CourseLecturesController extends Controller
{
    public function index(Course $course)
    {
        return response()->json($course->lectures);
    }

    public function store(LectureStoreRequest $request, Course $course)
    {
        $lecture = $request->storeLecture($course);
        return response()->json(['lecture' => $lecture, 'message' => 'Lecture created successfully'], 201);
    }

    public function show(Course $course, Lecture $lecture)
    {
        if ($lecture->course_id !== $course->id) {
            return response()->json(['error' => 'Lecture does not belong to this course'], 404);
        }
        return response()->json($lecture);
    }

    public function update(LectureUpdateRequest $request, Course $course, Lecture $lecture)
    {
        if ($lecture->course_id != $course->id) {
            return response()->json(['error' => 'Lecture does not belong to this course'], 404);
        }
        $lecture = $request->updateLecture($lecture);
        return response()->json(['lecture' => $lecture, 'message' => 'Lecture updated successfully']);
    }

    public function destroy(Course $course, Lecture $lecture)
    {
        if ($lecture->course_id !== $course->id) {
            return response()->json(['error' => 'Lecture does not belong to this course'], 404);
        }
        $lecture->remove();
        return response()->json(['message' => 'Lecture deleted successfully']);
    }
}
