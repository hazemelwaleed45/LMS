<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentInterest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentInterestController extends Controller
{
   
    public function index()
    {
        $interests = StudentInterest::where('user_id', Auth::id())->get();
        return response()->json($interests);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'interest' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $interest = StudentInterest::create([
            'user_id' => Auth::id(),
            'interest' => $request->interest
        ]);

        return response()->json(['message' => 'Interest added', 'data' => $interest], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'interest' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $interest = StudentInterest::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$interest) {
            return response()->json(['error' => 'Interest not found'], 404);
        }

        $interest->update(['interest' => $request->interest]);

        return response()->json(['message' => 'Interest updated', 'data' => $interest]);
    }

    public function destroy($id)
    {
        $interest = StudentInterest::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$interest) {
            return response()->json(['error' => 'Interest not found'], 404);
        }

        $interest->delete();

        return response()->json(['message' => 'Interest deleted']);
    }

    public function showUserInterests(Request $request)
    {
        $user = auth()->user(); 

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $interests = StudentInterest::where('user_id', $user->id)->get();

        return response()->json(['data' => $interests], 200);
    }

}

