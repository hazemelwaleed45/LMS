<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LandingPageController extends Controller
{
    public function getInstructors()
    {
        // Cache the data for 60 minutes
        $data = Cache::remember('instructors_list', 60, function () {
            $instructors = Instructor::with(['courses:id,title,instructor_id'])->get(['id', 'name', 'image', 'about']);

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

    public function getInstructor($id)
    {
        $instructor = Instructor::with(['courses:id,title,instructor_id', 'courses.enrollments'])->find($id);
        if (!$instructor) {
            return response()->json(['error' => 'Instructor not found'], 404);
        }

        // Calculate total students and courses
        $totalStudents = 0;
        $totalCourses = $instructor->courses->count();
        foreach ($instructor->courses as $course) {
            $totalStudents += $course->enrollments->count();
        }

        $data = [
            'id' => $instructor->id,
            'name' => $instructor->name,
            'image' => $instructor->image
                ? Storage::disk('public')->url('images/instructors/' . $instructor->image)
                : null,
            'about' => $instructor->about,
            'rate' => $instructor->averageRating(),
            'courses' => $instructor->courses ? $instructor->courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title
                ];
            }) : [],
            'students_count' => $totalStudents,
        ];

        return response()->json(['data' => $data], 200);
    }

    public function sendContactMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'name'    => $request->name,
            'email'   => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        $adminEmail = env('MAIL_FROM_ADDRESS', 'support@learn-on.com'); // Ensure this email matches the domain

        // Send email to admin
        Mail::raw("New Contact Request\n\nName: {$data['name']}\nEmail: {$data['email']}\nSubject: {$data['subject']}\nMessage: {$data['message']}", function ($mail) use ($data, $adminEmail) {
            $mail->to($adminEmail)
                ->subject($data['subject'])
                ->from($adminEmail, 'LearnOn Platform') // Use only authorized email here
                ->replyTo($data['email'], $data['name']); // Set the sender email as Reply-To
        });

        // Send thank-you email to user
        Mail::raw("Dear {$data['name']},\n\nThank you for reaching out to us. We have received your message and will respond shortly.\n\nBest regards,\nLearnOn Platform", function ($mail) use ($data, $adminEmail) {
            $mail->to($data['email'])
                ->subject("Thank You for Contacting Us")
                ->from($adminEmail, 'LearnOn Platform'); // Use platform's email only
        });

        return response()->json(['message' => 'Your message has been sent successfully!'], 200);
    }
}
