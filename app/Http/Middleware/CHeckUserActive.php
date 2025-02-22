<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserActive
{
    public function handle(Request $request, Closure $next)
    {
        // Allow unauthenticated users to proceed (for login, registration)
        if (!Auth::check()) {
            return $next($request);
        }
    
        // Now check only authenticated users
        $user = Auth::user();
    
        if ($user && $user->active == 2) {
            return response()->json(['message' => 'Your account is blocked. Contact admin.'], 403);
        }
    
        return $next($request);
    }
    
}
