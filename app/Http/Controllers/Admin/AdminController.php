<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    // display all admins

    public function index()
    {
        $admins = User::where('role', 'admin')->get();
        return response()->json(['data' => $admins], 200);
    }

    public function show($id)
    {
        $admin = User::where('role', 'admin')->where('id', $id)->first();
        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }
        return response()->json(['data' => $admin], 200);
    }
    // Add new admin
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json(['data' => $user], 200);
    }

    // // Edit data of admin
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:admin',
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
        return response()->json(['data' => $user], 200);
    }

    // Delete admin
    public function destroy($id)
    {
        $admin = User::where('role', 'admin')->where('id', $id)->first();
        if (!$admin) {

            return response()->json(['error' => 'Admin not found'], 404);
        }

        $admin->delete();
        return response()->json(['message' => 'Admin deleted successfully'], 200);
    }
}
