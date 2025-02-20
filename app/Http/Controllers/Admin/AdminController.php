<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function getBlockedUsers()
    {
        $blockedUsers = User::where('active', 2)->get();
        return response()->json($blockedUsers);
    }

    public function unblockUser($id)
    {
        $user = User::find($id);
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        $user->update(['active' => 0]);
    
        return response()->json(['message' => 'User has been unblocked']);
    }
    public function index()
    {
        $admins = Admin::with(['user' => function($query) {
            $query->select('id', 'email'); 
        }])
        ->get(['id', 'user_id', 'name', 'paypal_account'])
        ->map(function ($admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->user ? $admin->user->email : null, 
                'paypal_account' => $admin->paypal_account,
            ];
        });
    
        return response()->json(['data' => $admins], 200);
    }
    public function store(Request $request)
    {
   
     $validator = Validator::make($request->all(), [
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
        'role' => 'required|string|in:admin',
        'name' => 'required|string|max:255', 
        'paypal_account' => 'nullable|string|max:255',
        ]);

    
        if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
        }

        
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        
        $admin = Admin::create([
            'user_id' => $user->id, 
            'name' => $request->name,
            'paypal_account' => $request->paypal_account,
        ]);

        return response()->json([
        'data' => [
            'user' => $user,
            'admin' => $admin,
        ],
        ], 200);
    }
    public function show($id)
    {
        $admin = User::where('role', 'admin')->where('id', $id)->first();
        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }
        return response()->json(['data' => $admin], 200);
    }
    public function update(Request $request, $id)
    {
    
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:admin',
            'name' => 'sometimes|string|max:255', 
            'paypal_account' => 'sometimes|nullable|string|max:255', 
        ]);

    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

    
        $user->update([
            'email' => $request->email ?? $user->email,
            'role' => $request->role ?? $user->role,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
        ]);

        
        $admin = Admin::where('user_id', $user->id)->first(); 
        if ($admin) {
        
            $admin->update([
                'name' => $request->name ?? $admin->name,
                'paypal_account' => $request->paypal_account ?? $admin->paypal_account,
            ]);
        } else {
        
            $admin = Admin::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'paypal_account' => $request->paypal_account,
            ]);
        }
        return response()->json([
            'data' => [
                'user' => $user,
                'admin' => $admin,
            ],
        ], 200);
    }
    public function destroy($id)
    {
        $user = User::where('role', 'admin')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['error' => 'Admin not found'], 404);
        }
        $admin = Admin::where('user_id', $user->id)->first();
        if ($admin) {
            $admin->delete();
        }
        $user->delete();
        return response()->json(['message' => 'Admin deleted successfully'], 200);
    }
}
