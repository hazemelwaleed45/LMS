<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    public function getInstructors()
    {
        // Cache the data for 60 minutes
        $data = Cache::remember('instructors_list', 60, function () {
            $instructors = Instructor::with(['courses:id,title,instructor_id'])->get(['id', 'name', 'image','about']);

            return $instructors->map(function ($instructor) {
                return [
                    'id' => $instructor->id,
                    'name' => $instructor->name,
                    'image' => $instructor->image
                        ? Storage::disk('public')->url('images/instructors/' . $instructor->image)
                        : null,
                    'about' => $instructor->about,
                    'rate' => $instructor->averageRating(),
                ];
            });
        });

        return response()->json(['data' => $data], 200);
    }
}
