<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'role' => 'required|in:instructor,student,admin',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userData = [
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'active' => 0
        ];

        if ($request->role === 'student') {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:students,username',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female',
                'country' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'education' => 'nullable|string|max:255',
                'image' => 'required|image|mimes:jpg,png,jpeg,gif|max:2048',
                'interests' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $request->image->storeAs('images/students', $imageName, 'public');
                $imagePath = $imageName;
            }

            $user = User::create($userData);
            $user->student()->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $request->username,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'country' => $request->country,
                'phone' => $request->phone,
                'education' => $request->education,
                'image' => $imagePath,
                'interests' => $request->interests,
            ]);
        } elseif ($request->role === 'instructor') {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'about' => 'nullable|string',
                'gender' => 'required|in:male,female',
                'date_of_birth' => 'required|date',
                'phone' => 'required|string|max:255',
                'paypal_account' => 'nullable|string|max:255',
                'major' => 'required|in:university,school,graduated',
                'image' => 'required|image|mimes:jpg,png,jpeg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imageName = time() . '.' . $request->image->getClientOriginalExtension();
                $request->image->storeAs('images/instructors', $imageName, 'public');
                $imagePath = $imageName;
            }

            $user = User::create($userData);
            $user->instructor()->create([
                'name' => $request->name,
                'about' => $request->about,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'phone' => $request->phone,
                'paypal_account' => $request->paypal_account,
                'major' => $request->major,
                'image' => $imagePath,
            ]);
        } elseif ($request->role === 'admin') {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'paypal_account' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::create($userData);
            $user->admin()->create([
                'name' => $request->name,
                'paypal_account' => $request->paypal_account,
            ]);
        } else {
            return response()->json(['error' => 'Invalid role specified'], 400);
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
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Check if the user is blocked
        if ($user->active == 2) {
            return response()->json(['message' => 'Your account is blocked. Contact admin.'], 403);
        }

        // // Delete expired tokens before checking active sessions
        // $user->tokens()->delete();

        // If the user is marked as "active" (previously logged in), check if this is a new login
        if ($user->active == 1) {
            // Block the user from logging in
            $user->update([
                'device_token' => null,
                'active' => 2 // Set as blocked

            ]);
            $user->tokens()->delete();

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
        $token = $request->bearerToken(); // Get the token from the request

        if (!$token) {
            return response()->json(['message' => 'No token provided'], 400);
        }

        // Find the token in the database
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if ($personalAccessToken) {
            // Get the associated user
            $user = $personalAccessToken->tokenable;

            // Revoke all tokens
            $user->tokens()->delete();

            // Clear device token and update status
            $user->update([
                'device_token' => null,
                'active' => 0,
            ]);

            return response()->json(['message' => 'Logout successful']);
        }

        // If token is expired or invalid, still clear the session
        $email = $request->input('email'); // Allow user to pass email if token is expired
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'device_token' => null,
                'active' => 0,
            ]);
        }

        return response()->json(['message' => 'Session expired, user logged out'], 200);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        $token = Password::createToken($user);

        $user->notify(new ResetPasswordNotification($token));

        return response()->json(['message' => 'Password reset link sent to your email.'], 200);
    }

    public function resetPassword(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validated->fails()) {
            return response()->json(['message' => 'Validation failed'], 400);
        }

        // Reset password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => bcrypt($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully'])
            : response()->json(['message' => 'Failed to reset password'], 400);
    }

    public function getUser(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role === 'student') {
            $userDetails = $user->student;
            $userDetails->image = asset('storage/app/public/images/students/' . $userDetails->image);
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

    public function updatePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successful.'], 200);
        }

        return response()->json(['message' => 'Invalid token or email.'], 400);
    }
}
