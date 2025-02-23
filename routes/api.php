<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StudentAdminController;
use App\Http\Controllers\Admin\CourseLecturesController;
use App\Http\Controllers\Admin\StudentController;
use Illuminate\Session\Middleware\StartSession;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Admin\MeetingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\StudentInterestController;
use App\Http\Controllers\Student\MyCoursesController;
use Illuminate\Session\Middleware\CheckUserActive;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// endpoints for landing page
Route::get('/courses', [StudentCourseController::class, 'getCourses']);
Route::get('/courses/{id}', [StudentCourseController::class, 'getCourse']);
Route::get('/courses/{id}/related', [StudentCourseController::class, 'getRelatedCourses']);
Route::get('/PopularCourses', [StudentCourseController::class, 'getPopularCourses']);

Route::get('instructors', [LandingPageController::class, 'getInstructors']);
Route::get('instructors/{id}', [LandingPageController::class, 'getInstructor']);


Route::middleware([StartSession::class])->group(function () {
    // Social Login Routes
    Route::get('/auth/{provider}', [SocialController::class, 'redirectToProvider']);
    Route::get('/auth/{provider}/callback', [SocialController::class, 'handleProviderCallback']);
    Route::post('/complete-profile', [SocialController::class, 'completeProfile']);
});

// create routes group for admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::apiResource('instructors', InstructorController::class)->except('update');
        Route::Post('instructors/{id}', [InstructorController::class, 'update']);

        Route::apiResource('admins', AdminController::class);

        Route::apiResource('students', StudentController::class)->except('update');
        Route::Post('students/{id}', [StudentController::class, 'update']);

        Route::apiResource('courses', CourseController::class)->except('update');
        Route::Post('courses/{id}', [CourseController::class, 'update']);
        Route::apiResource('courses.lectures', CourseLecturesController::class)->except('update');
        Route::Post('courses/{course}/lectures/{lecture}', [CourseLecturesController::class, 'update']);

        Route::apiResource('categories', CategoryController::class);

        Route::get('payments/export', [PaymentController::class, 'exportPaymentsReport']);
        Route::get('purchase', [PaymentController::class, 'getPaymentsReport']);

        Route::prefix('pages')->group(function () {
            Route::get('{pageName}', [PageController::class, 'show']); // Get content for a page
            Route::post('{pageName}', [PageController::class, 'update']); // Update content for a page
            Route::post('/upload-image/{pageName}', [PageController::class, 'uploadImage']);
            Route::post('/append-to-page/{pageName}', [PageController::class, 'appendToPage']);
        });


        Route::get('/zoom/connect', [MeetingController::class, 'redirectToZoom']);
        Route::get('/zoom/callback', [MeetingController::class, 'handleZoomCallback']);
        Route::post('/meetings', [MeetingController::class, 'createMeeting']);
    });
});


Route::middleware(['auth:sanctum', 'ensure.single.device', 'role:student', 'check.active'])->group(function () {
    Route::prefix('student')->group(function () {
        Route::get('/profile', [AuthController::class, 'getUser']);
        //update student profile
        Route::post('edit/{id}', [StudentController::class, 'update']);
        //cart
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/add', [CartController::class, 'addToCart']);
        Route::delete('/cart/remove/{id}', [CartController::class, 'removeFromCart']);
        Route::post('/cart/checkout', [CartController::class, 'checkout']);

        Route::get('payment-history', [PaymentController::class, 'getPaymentHistory']); // student

        Route::prefix('/mycourses/{course}/lectures')->group(function () {
            Route::get('/{lecture}', [MyCoursesController::class, 'getLectureDetails']);
        });

        Route::get('/mycourses', [StudentCourseController::class, 'myCourses']);
        Route::get('/mycourses/{course}', [StudentCourseController::class, 'view']);
        Route::get('/courses/{courseId}/feedback', [StudentCourseController::class, 'getCourseFeedback']);
        Route::post('/courses/{courseId}/feedback', [StudentCourseController::class, 'submitCourseFeedback']);


        // interested routes

        Route::get('/interests/get', [StudentInterestController::class, 'index']);
        Route::post('/interests/add', [StudentInterestController::class, 'store']);
        Route::put('/interests/update/{id}', [StudentInterestController::class, 'update']);
        Route::delete('/interests/delete/{id}', [StudentInterestController::class, 'destroy']);
        Route::get('/interests/list', [StudentInterestController::class, 'showUserInterests']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/paypal-payment', [PaymentController::class, 'paypalPayment']);
});
Route::get('/paypal-success', [PaymentController::class, 'paypalSuccess'])->name('paypal.success');
Route::get('/paypal-cancel', [PaymentController::class, 'paypalCancel'])->name('paypal.cancel');

Route::get('payments/export', [PaymentController::class, 'exportPaymentsReport']);
Route::get('purchase', [PaymentController::class, 'getPaymentsReport']);
Route::post('payments/process', [PaymentController::class, 'processPayment']);

Route::post('payments/prepare', [PaymentController::class, 'createPaymentMethod']);

Route::middleware(['auth:sanctum', 'check.active'])->group(function () {
    Route::get('/admin/blocked-users', [AdminController::class, 'getBlockedUsers']);
    Route::post('/admin/unblock-user/{id}', [AdminController::class, 'unblockUser']);
});
