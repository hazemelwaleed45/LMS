<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller {
    // Fetch Cart
    public function index() 
    {
        
        if (!Auth::check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        $student = DB::table('students')->where('user_id', Auth::id())->get()->first();

        $cart = Cart::where('student_id', $student->id)->with('course')->get();
        return response()->json(['cart' => $cart]);
    }

    // Add Course to Cart
    public function addToCart(Request $request) 
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        $studentId = Auth::id();

        //dd($studentId);
        // Check if the student exists
        $studentExists = DB::table('students')->where('user_id', $studentId)->exists();
        $student = DB::table('students')->where('user_id', $studentId)->get()->first();

        if (!$studentExists) {
            return response()->json(['error' => 'Student record not found'], 400);
        }

        $course = Course::find($request->course_id);

        //dd($course->price , $student);
        $cart = Cart::Create(
            ['student_id' => $student->id, 'course_id' => $request->course_id,
            'quantity' => 1, 'price' => $course->price]
        );

        return response()->json(['message' => 'Course added to cart', 'cart' => $cart]);
    }

    // Remove Course from Cart
    public function removeFromCart($id) 
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        $student = DB::table('students')->where('user_id', Auth::id())->get()->first();

        $cart = Cart::where('student_id', $student->id)->where('course_id', $id)->first();
        if (!$cart) {
            return response()->json(['message' => 'Course not found in cart'], 404);
        }

        $cart->delete();
        return response()->json(['message' => 'Course removed from cart']);
    }

    // Checkout (Convert Cart to Order)
    public function checkout() 
    {
        $student = DB::table('students')->where('user_id', Auth::id())->get()->first();

        $cartItems = Cart::where('student_id', $student->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $totalAmount = $cartItems->sum('price');

        // Create Order (We’ll implement this in Step 3)
        
        // Clear Cart after checkout
        Cart::where('student_id', Auth::id())->delete();

        return response()->json(['message' => 'Checkout successful', 'total' => $totalAmount]);
    }
}

