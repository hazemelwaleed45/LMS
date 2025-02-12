<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Student;
use App\Models\Instructor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SocialController extends Controller
{

    public function redirectToProvider()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Handle Google callback
    // public function handleProviderCallback()
    // {
    //     try {
    //         $socialUser = Socialite::driver('google')->stateless()->user();
    //     } catch (\Exception $e) {
    //         // Log the error
    //         \Log::error('Google OAuth Error: ' . $e->getMessage());
    //         return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 500);
    //     }
    
    //     // Find or create user
    //     $user = User::firstOrCreate(
    //         ['email' => $socialUser->getEmail()],
    //         [
    //             'email' => $socialUser->getEmail(),
    //             'name' => $socialUser->getName(),
    //             'password' => bcrypt(Str::random(24)),
    //             'role' => 'student',
    //         ]
    //     );
        
    //     // Check if student profile exists
    //     if ($user->role === 'student' && !$user->student) {
    //     return response()->json([
    //         'message' => 'Profile incomplete',
    //         'user' => [
    //             'id' => $user->id,
    //             'email' => $user->email,
    //             'role' => $user->role,
    //         ],
    //         'profile_needed' => true, // This tells the frontend to show profile form
    //     ], 200);
    //     }
    //     // Log in the user
    //     Auth::login($user);
    
    //     // Generate token
    //     $token = $user->createToken('auth_token')->plainTextToken;
    //     $tokenParts = explode('|', $token);
    //     return response()->json([
    //         'token' => $tokenParts,
    //         'user' => [
    //             'id' => $user->id,
    //             'email' => $user->email,
    //             'role' => $user->role,
    //         ],
    //         'profile_needed' => false,

    //     ], 200);
    
    // }

    public function handleProviderCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 500);
        }

        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'email' => $socialUser->getEmail(),
                'name' => $socialUser->getName(),
                'password' => bcrypt(Str::random(24)),
                'role' => 'student',
            ]
        );

        if ($user->role === 'student' && !$user->student) {
            return response()->json([
                'message' => 'Profile incomplete',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'profile_needed' => true,
            ], 200);
        }

        // Generate a new device token
        $deviceToken = Str::random(32);

        // If already logged in from another device, force logout
        if ($user->device_token) {
            $user->tokens()->delete();
        }

        // Update with new device token
        $user->update(['device_token' => $deviceToken]);

        // Generate new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'profile_needed' => false,
            'message' => 'Login successful. Previous session (if any) has been logged out.'
        ], 200);
    }

    public function completeProfile(Request $request)
    {
        $rules = [
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
        ];
    
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Find the user
        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        // Ensure role is set to student
        $user->update(['role' => 'student']);
    
        // Save student details
        $user->student()->create($request->only([
            'first_name', 'last_name', 'username', 'date_of_birth',
            'gender', 'country', 'phone', 'education', 'image', 'interests'
        ]));
    
        return response()->json(['message' => 'Profile updated successfully'], 200);
    }
    

}
        // 🌐 Redirect to Apple
    // public function redirectToProvider($provider)
    // {
    //     return Socialite::driver($provider)->stateless()->redirect();
    // }

    // 🌐 Handle Apple Callback
    // public function handleProviderCallback($provider)
    // {
    //     if ($provider === 'apple') {
    //         $token = request()->input('token');

    //         $clientId = config('services.apple.client_id');
    //         $teamId = config('services.apple.team_id');
    //         $keyId = config('services.apple.key_id');
    //         $privateKey = config('services.apple.private_key');

    //         $jwt = $this->generateAppleJWT($clientId, $teamId, $keyId, $privateKey);

    //         $socialUser = Socialite::driver('apple')->userFromToken($jwt);

    //         return $this->loginOrCreateUser($socialUser);
    //     }

    //     // Other providers (Google, Facebook)
    //     $socialUser = Socialite::driver($provider)->stateless()->user();

    //     return $this->loginOrCreateUser($socialUser);
    // }
    
    // // 🌐 Generate Apple JWT
    // private function generateAppleJWT($clientId, $teamId, $keyId, $privateKey)
    // {
    //     $issuedAt = time();
    //     $expiration = $issuedAt + (60 * 10); // 10 minutes

    //     $payload = [
    //         'iss' => $teamId,
    //         'iat' => $issuedAt,
    //         'exp' => $expiration,
    //         'aud' => 'https://appleid.apple.com',
    //         'sub' => $clientId,
    //     ];

    //     return JWT::encode($payload, $privateKey, 'ES256', $keyId);
    // }

    // // 🌐 Login or Create User
    // private function loginOrCreateUser($socialUser)
    // {
    //     $user = User::firstOrCreate(
    //         ['email' => $socialUser->getEmail()],
    //         [
    //             'email' => $socialUser->getEmail(),
    //             'name' => $socialUser->getName(),
    //             'password' => bcrypt(str_random(24)), // Random password
    //         ]
    //     );

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json(['token' => $token], 200);
    // }