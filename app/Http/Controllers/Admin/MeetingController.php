<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ZoomService;
use App\Models\Course;

class MeetingController extends Controller
{
    //

    protected $zoomService;

    // حقن ZoomService في Constructor
    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    /**
     * توجيه المستخدم إلى Zoom للتحقق من OAuth.
     */
    public function redirectToZoom()
    {
        // إنشاء رابط التوجيه إلى Zoom
        $authorizationUrl = $this->zoomService->getAuthorizationUrl();

        // توجيه المستخدم إلى Zoom
        return redirect($authorizationUrl);
    }

    /**
     * معالجة الرد من Zoom بعد التحقق.
     */
    public function handleZoomCallback(Request $request)
    {
        // الحصول على الـ Code من Zoom
        $code = $request->query('code');

        // الحصول على Access Token باستخدام الـ Code
        $accessToken = $this->zoomService->getAccessToken($code);

        // حفظ الـ Access Token في الجلسة (أو قاعدة البيانات)
        session(['zoom_access_token' => $accessToken->getToken()]);

        // إعادة رد نجاح
        return response()->json([
            'message' => 'Zoom connected successfully!',
            'access_token' => $accessToken->getToken(),
        ]);
    }

    /**
     * إنشاء اجتماع Zoom.
     */
    public function createMeeting(Request $request)
    {
        // التحقق من أن المستخدم مدرس أو أدمن
        if (!auth()->user()->isAdmin() && !auth()->user()->isInstructor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // الحصول على الـ Access Token من الجلسة
        $accessToken = session('zoom_access_token');

        // بيانات الاجتماع
        $meetingData = [
            'topic' => $request->input('topic', 'Course Meeting'),
            'type' => 2, // Scheduled meeting
            'start_time' => $request->input('start_time', now()->addDays(1)->toIso8601String()),
            'duration' => $request->input('duration', 60), // Duration in minutes
            'timezone' => $request->input('timezone', 'Africa/Cairo'),
        ];

        // إنشاء الاجتماع باستخدام ZoomService
        $meeting = $this->zoomService->createMeeting($accessToken, $meetingData);

        // حفظ رابط الاجتماع في قاعدة البيانات
        $course = Course::find($request->course_id);
        $course->zoom_meeting_link = $meeting['join_url'];
        $course->save();

        // إعادة رد نجاح مع بيانات الاجتماع
        return response()->json([
            'message' => 'Meeting created successfully!',
            'meeting' => $meeting,
        ]);
    }

    /**
     * إظهار رابط الاجتماع للطالب.
     */
    public function showMeeting($courseId)
    {
        // العثور على الكورس
        $course = Course::find($courseId);

        // التحقق من أن الطالب مسجل في الكورس
        if (!auth()->user()->isEnrolled($course)) {
            return response()->json(['error' => 'You are not enrolled in this course'], 403);
        }

        // إعادة رابط الاجتماع
        return response()->json([
            'course' => $course,
            'meeting_link' => $course->zoom_meeting_link,
        ]);
    }
}
