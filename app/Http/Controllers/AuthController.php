<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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

    // public function login(Request $request)
    // {
    //     $rules = [
    //         'email' => 'required|email',
    //         'password' => 'required',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     if (!Auth::attempt($request->only('email', 'password'))) {
    //         return response()->json(['message' => 'Invalid credentials'], 401);
    //     }
    //     $user = Auth::user();
    //     $token = $request->user()->createToken('auth_token')->plainTextToken;
    //     $tokenParts = explode('|', $token);
    //     return response()->json([
    //         'token' => $tokenParts,
    //         'user' => [
    //             'id' => $user->id,
    //             'email' => $user->email,
    //             'role' => $user->role,
    //         ],
    //     ], 200);
    // }
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

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // Generate a new unique device token
        $deviceToken = Str::random(32);

        // If the user is already logged in from another device, force logout
        if ($user->device_token) {
            $user->tokens()->delete(); // Revoke all previous tokens
        }

        // Update user record with new device token
        $user->update(['device_token' => $deviceToken]);

        // Generate new authentication token
        $token = $user->createToken('auth_token')->plainTextToken;
        $parts = explode('|', $token);
        $tokenId = $parts[0];
        $tokenPlain = $parts[1];
        if (count($parts) !== 2) {
            return response()->json(['error' => 'Invalid token format'], 400);
        }
        $tokens = PersonalAccessToken::find($tokenId);

        if (!$tokens || !$tokens->token) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        if (!hash_equals($tokens->token, hash('sha256', $tokenPlain))) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        $user = $tokens->tokenable;
        return response()->json(['user' => $user]);

        // return response()->json([
        //     'message' => 'Login successful',
        //     'token' => $token,
        //     'user' => [
        //         'id' => $user->id,
        //         'email' => $user->email,
        //         'role' => $user->role,
        //         'details' => $userDetails,
        //     ]
        // ], 200);
        // return response()->json([
        //     'token' => $token,
        //     'user' => [
        //         'id' => $user->id,
        //         'email' => $user->email,
        //         'role' => $user->role,
        //     ],
        //     'message' => 'Login successful. Previous session (if any) has been logged out.'
        // ], 200);
    }
//     public function login(Request $request)
// {
//     $rules = [
//         'email' => 'required|email',
//         'password' => 'required',
//     ];

//     $validator = Validator::make($request->all(), $rules);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }

//     if (!Auth::attempt($request->only('email', 'password'))) {
//         return response()->json(['message' => 'Invalid credentials'], 401);
//     }

//     $user = Auth::user();
//     $deviceToken = Str::random(32);
//     if ($user->device_token) {
//         $user->tokens()->delete();
//     }
//     $user->update(['device_token' => $deviceToken]);

//     $token = $user->createToken('auth_token')->plainTextToken;


//     $request->headers->set('Authorization', 'Bearer ' . $token);

//     // Get user details using the existing getUser function
//     $userResponse = $this->getUser($request);

//     // // Extract response data
//     $userData = json_decode($userResponse->getContent(), true);

//     // Add the token to the response
//     $userData['token'] = $token;

//     return response()->json($userData, 200);
// }

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
        $userDetails = $user->admin;
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

    public function logout(Request $request)
    {
        $user = $request->user();

        // Revoke all user tokens
        $user->tokens()->delete();

        // Clear the device token
        $user->update(['device_token' => null]);

        return response()->json(['message' => 'Logout successful']);
    }

    // public function logout(Request $request)
    // {
    //     $request->user()->tokens()->delete();

    //     return response()->json(['message' => 'Logout successful']);
    // }

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

}