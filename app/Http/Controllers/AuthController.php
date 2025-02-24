<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'role' => 'required|in:instructor,student',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);


        if ($request->role === 'student') {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:students,username',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female',
                'country' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'education' => 'nullable|string|max:255',
                'image' => 'nullable|string|max:255',
                'interests' => 'nullable|string|max:255',
            ]);

            $user->student()->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $request->username,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'country' => $request->country,
                'phone' => $request->phone,
                'education' => $request->education,
                'image' => $request->image,
            ]);
        } elseif ($request->role === 'instructor') {

            $request->validate([
                'name' => 'required|string|max:255',
                'about' => 'nullable|string',
                'gender' => 'required|in:male,female',
                'date_of_birth' => 'required|date',
                'phone' => 'required|string|max:255',
                'paypal_account' => 'nullable|string|max:255',
                'major' => 'required|in:university,school,graduated',
                'image' => 'nullable|string|max:255',
            ]);

            $user->instructor()->create([
                'name' => $request->name,
                'about' => $request->about,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'phone' => $request->phone,
                'paypal_account' => $request->paypal_account,
                'major' => $request->major,
                'image' => $request->image,
            ]);
        } elseif ($request->role === 'admin') {
            $request->validate([
                'name' => 'required|string|max:255',
                'paypal_account' => 'nullable|string|max:255',
            ]);

            $user->admin()->create([
                'name' => $request->name,
                'paypal_account' => $request->paypal_account,
            ]);
        }

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Retrieve the user FIRST before checking authentication
        $user = User::where('email', $request->email)->first();

        // If user doesn't exist, return invalid credentials
        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // If the specific user is blocked, prevent login
        if ($user->active == 2) {
            return response()->json(['message' => 'Your account is blocked. Contact admin.'], 403);
        }

        // Now, attempt authentication AFTER ensuring the user isn't blocked
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }


        // Refresh user data after login
        $user = Auth::user();

        if ($user->active == 1) {
            // Force logout all active sessions
            $user->tokens()->delete();

            // Block the user from logging in
            $user->update([
                'device_token' => null,
                'active' => 2
            ]);

            return response()->json(['message' => 'You have been blocked for attempting to log in from another device. Contact admin.'], 403);
        }

        // Generate a new device token
        $deviceToken = Str::random(32);

        // Update user status and device token
        $user->update([
            'device_token' => $deviceToken,
            'active' => 1, // Mark as active
        ]);

        // Generate new authentication token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'device_token' => $deviceToken,
            'message' => 'Login successful.'
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Revoke all user tokens
        $user->tokens()->delete();

        // Clear the device token and set active status to 0
        $user->update([
            'device_token' => null,
            'active' => 0, // Mark as logged out
        ]);

        return response()->json(['message' => 'Logout successful']);
    }
    public function forgotPassword(Request $request)
    {
        $rules = [
            'email' => 'required|email',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otp = rand(1000, 9999);
        $otpExpiresAt = Carbon::now()->addMinutes(10);

        $user->update([
            'remember_token' => $otp,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        Mail::to($user->email)->send(new OtpMail($otp, 'forget_password'));

        return response()->json(['message' => 'OTP sent to your email'], 200);
    }

    public function resetPassword(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'otp' => 'required|numeric',
            'password' => 'required|confirmed|min:8',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->remember_token !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        if (Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP has expired'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $user->update([
            'remember_token' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password reset successful'], 200);
    }
    public function getUser(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role === 'student') {
            $userDetails = $user->student;
        } elseif ($user->role === 'instructor') {
            $userDetails = $user->instructor;
        } elseif ($user->role === 'admin') {
            $userDetails = [
                'name' => $user->admin->name,
                'paypal_account' => $user->admin->paypal_account,
            ];
        } else {
            $userDetails = null;
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'details' => $userDetails,
            ]
        ], 200);
    }

}
