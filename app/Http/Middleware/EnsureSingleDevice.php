<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureSingleDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check if the request matches the stored device token
        if ($user && $user->device_token !== $request->header('Device-Token')) {
            $user->tokens()->delete(); // Logout all sessions
            return response()->json(['message' => 'Session expired. Please log in again.'], 403);
        }

        return $next($request);
    }
}
