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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
class SocialController extends Controller
{

    public function redirectToProvider()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // public function handleProviderCallback()
    // {   

    //     try {
    //         $socialUser = Socialite::driver('google')->stateless()->user();
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Authentication failed: ' . $e->getMessage()], 500);
    //     }

    //     // Find or create the user
    //     $user = User::firstOrCreate(
    //         ['email' => $socialUser->getEmail()],
    //         [
    //             'email' => $socialUser->getEmail(),
    //             'name' => $socialUser->getName(),
    //             'password' => bcrypt(Str::random(24)),
    //             'role' => 'student',
    //         ]
    //     );

    //     // Check if the user is blocked
    //     if ($user->active == 2) {
    //         return response()->json(['message' => 'Your account is blocked. Contact admin.'], 403);
    //     }

    //     // Authenticate user manually
    //     Auth::login($user);

    //     // Fetch the authenticated user
    //     $user = Auth::user();

    //     // Allow admin multi-login
    //     if ($user->role === 'admin') {
    //         $token = $user->createToken('auth_token')->plainTextToken;
    //         return response()->json([
    //             'token' => $token,
    //             'user' => [
    //                 'id' => $user->id,
    //                 'email' => $user->email,
    //                 'role' => $user->role,
    //             ],
    //             'message' => 'Admin login successful.'
    //         ], 200);
    //     }

    //     // If a student is already logged in, block them
    //     if ($user->active == 1) {
    //         $user->tokens()->delete(); // Force logout
    //         $user->update([
    //             'device_token' => null,
    //             'active' => 2
    //         ]);

    //         return response()->json(['message' => 'You have been blocked for attempting to log in from another device. Contact admin.'], 403);
    //     }

    //     // Generate a new device token
    //     $deviceToken = Str::random(32);

    //     // Update user status and device token
    //     $user->update([
    //         'device_token' => $deviceToken,
    //         'active' => 1, // Mark as active
    //     ]);

    //     // Generate new authentication token
    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'token' => $token,
    //         'user' => [
    //             'id' => $user->id,
    //             'email' => $user->email,
    //             'role' => $user->role,
    //         ],
    //         'message' => 'Login successful.'
    //     ], 200);
    // }
    public function handleProviderCallback(Request $request)
{   
    try {
        $socialUser = Socialite::driver('google')->stateless()->user();
    } catch (\Exception $e) {
        return response()->json(['error' => 'Authentication failed'], 500);
    }

    // Find user by email
    $user = User::where('email', $socialUser->getEmail())->first();

    // If user doesn't exist, create a new one
    if (!$user) {
        $user = User::create([
            'email' => $socialUser->getEmail(),
            'name' => $socialUser->getName(),
            'password' => bcrypt(Str::random(24)),
            'role' => 'student',
            'active' => 0,
        ]);
    }

    // Check if the user is blocked
    if ($user->active == 2) {
        return response()->json(['message' => 'Your account is blocked. Contact admin.'], 403);
    }

    // If already logged in (active = 1), block them
    if ($user->active == 1) {
        $user->tokens()->delete(); 
        $user->update([
            'device_token' => null,
            'active' => 2 
        ]);

        return response()->json(['message' => 'You have been blocked for multiple logins.'], 403);
    }

    // Authenticate the user
    Auth::login($user, true);

    // Refresh user data
    $user = Auth::user();

    // Revoke old tokens
    $user->tokens()->delete();

    // Generate a new token
    $token = $user->createToken('auth_token')->plainTextToken;

    // Generate or use an existing device token
    $requestDeviceToken = $request->header('Device-Token') ?? Str::random(32);
    $user->update([
        'device_token' => $requestDeviceToken,
        'active' => 1, 
    ]);

    return response()->json([
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ],
        'message' => 'Login successful.'
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