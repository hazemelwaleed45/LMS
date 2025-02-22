<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Exception;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Stripe\Charge;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\Payment as PayPalPayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use App\Models\PlatformSetting;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsReportExport;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentMethod;
use Laravel\Sanctum\PersonalAccessToken;

class PaymentController extends Controller
{
    public function getPaymentsReport()
    {
        // Query the database to retrieve the required information
        $payments = DB::table('payments')
            ->join('students', 'payments.student_id', '=', 'students.id')
            ->join('enrollment', function ($join) {
                $join->on('enrollment.course_id', '=', 'payments.course_id')
                     ->on('enrollment.student_id', '=', 'payments.student_id');
            })
            ->join('courses', 'payments.course_id', '=', 'courses.id') // Assuming a `courses` table exists
            ->join('instructors', 'courses.instructor_id', '=', 'instructors.id') // Assuming `courses` table has `instructor_id`
            ->select(
                'payments.id as payment_id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) as student_name"),
                'instructors.name as instructor_name',
                'courses.title as course_name',
                'payments.amount as amount_paid',
                'payments.payment_method'
            )
            ->where('payments.payment_status', 'completed')
            ->orderBy('payments.payment_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function exportPaymentsReport()
    {
        return Excel::download(new PaymentsReportExport, 'payments_report.xlsx');
    }

    public function getPaymentHistory(Request $request)
    {
        $user = auth()->guard('sanctum')->user();
        dd($user);
        // Ensure the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

            // Ensure the user is a student
        if ($user->role !== 'student') {
        return response()->json(['message' => 'Only students can access payment history'], 403);
        }
        // Get the student record associated with the user
        $student = DB::table('students')->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Student record not found'], 404);
        }

        // Fetch payment history for the authenticated student
        $payments = DB::table('payments')
            ->join('courses', 'payments.course_id', '=', 'courses.id')
            ->select(
                'payments.payment_date',
                'courses.title as course_name',
                'payments.payment_method',
                'payments.amount'
            )
            ->where('payments.student_id', $student->id)
            ->where('payments.payment_status', 'completed')
            ->orderBy('payments.payment_date', 'desc')
            ->get();

        if ($payments->isEmpty()) {
            return response()->json(['message' => 'No payment history found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    protected $paypalService;
    protected $apiContext; // For PayPal REST API SDK

    public function __construct(PayPalService $paypalService)
    {
        $this->paypalService = $paypalService;

        // Initialize PayPal API Context
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                config('paypal.sandbox.client_id'), // PayPal Client ID
                config('paypal.sandbox.client_secret') // PayPal Secret
            )
        );

        // Set PayPal API mode (sandbox or live)
        $this->apiContext->setConfig([
            'mode' => config('paypal.mode', 'sandbox'), // Use 'sandbox' or 'live'
        ]);
    }

    
    public function createPaymentMethod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:visa,Mastercard'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        // Initialize Stripe with secret key
        Stripe::setApiKey(env('STRIPE_SECRET'));
    
        try {
            // Create a payment method using Stripe's test tokens
            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'token' => 'tok_visa' // Test token (use 'tok_mastercard' for Mastercard)
                ],
            ]);
    
            return response()->json(['payment_method_id' => $paymentMethod->id], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

    public function processPayment(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'cart' => 'required|array',
                'cart.*.course_id' => 'required|integer',
                'cart.*.amount' => 'required|numeric|min:1',
                'cart.*.instructor_id' => 'required|integer',
                'payment_method_id' => 'required|string',
            ]);
    
            // Get authenticated user ID
            $userId = auth()->id();
    
            // Retrieve the corresponding student ID from the students table
            $student = \App\Models\Student::where('user_id', $userId)->first();
    
            if (!$student) {
                return response()->json(['error' => 'Student not found for this user'], 400);
            }
    
            $studentId = $student->id;
    
            // Initialize Stripe
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
    
            // Calculate total amount in cents
            $totalAmount = collect($request->cart)->sum('amount') * 100;
    
            // Create a PaymentIntent with automatic payment methods
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $totalAmount,
                'currency' => 'USD',
                'payment_method' => $request->payment_method_id,
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ]
            ]);
    
            // Get the base transaction code
            $baseTransactionCode = $paymentIntent->id;
    
            // Save payments for each course (Ensure unique transaction codes)
            foreach ($request->cart as $index => $item) {
                $uniqueTransactionCode = $baseTransactionCode . '_' . $item['course_id'];
    
                // Ensure no duplicate transaction for the same course
                $existingPayment = Payment::where('transaction_code', $uniqueTransactionCode)->first();
                if ($existingPayment) {
                    return response()->json(['error' => 'Duplicate transaction detected for course: ' . $item['course_id']], 400);
                }
    
                Payment::create([
                    'amount' => $item['amount'],
                    'currency' => 'USD',
                    'payment_method' => $request->payment_method_id,
                    'payment_date' => now(),
                    'transaction_code' => $uniqueTransactionCode, // Unique for each course
                    'payment_status' => 'completed',
                    'course_id' => $item['course_id'],
                    'student_id' => $studentId, 
                ]);

                    Enrollment::create([
                    'course_id' => $item['course_id'],
                    'student_id' => $studentId,
                ]);
            }
    
            return response()->json([
                'message' => 'Payment processed successfully',
                'transaction_code' => $baseTransactionCode,
            ], 200);
        } catch (\Stripe\Exception\CardException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    private function isValidCardNumber($number)
    {
        $number = preg_replace('/\D/', '', $number);
        $sum = 0;
        $alt = false;
    
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = $number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) $n -= 9;
            }
            $sum += $n;
            $alt = !$alt;
        }
        return ($sum % 10 == 0);
    }
    
    public function paypalPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart' => 'required|array|min:1',
            'cart.*.course_id' => 'required|exists:courses,id',
            'cart.*.amount' => 'required|numeric|min:1',
            'cart.*.instructor_id' => 'required|exists:instructors,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        $cart = $request->cart;
        $student = $request->user();
    
        if (!$student) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    
        $studentRecord = Student::where('user_id', $student->id)->first();
        if (!$studentRecord) {
            return response()->json(['error' => 'Student record not found'], 404);
        }
    
        DB::beginTransaction();
        try {
            $totalAmount = collect($cart)->sum('amount');
    
            $paypal = new PayPalClient;
            $paypal->setApiCredentials(config('paypal'));
            $paypal->getAccessToken();
    
            $response = $paypal->createOrder([
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "amount" => [
                            "currency_code" => "USD",
                            "value" => $totalAmount,
                        ],
                    ],
                ],
                "application_context" => [
                    "return_url" => route('paypal.success'),
                    "cancel_url" => route('paypal.cancel'),
                ],
            ]);
    
            Log::info('PayPal Order Created', ['response' => $response]);
    
            if (!isset($response['id'])) {
                throw new \Exception('Failed to create PayPal order: ' . json_encode($response));
            }
    
            DB::commit();
            $approvalUrl = collect($response['links'])->firstWhere('rel', 'approve')['href'];
    
            return response()->json([
                'success' => true,
                'message' => 'Redirect to PayPal for payment.',
                'approval_url' => $approvalUrl,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayPal Payment Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

    public function paypalSuccess(Request $request)
    {
        $paymentId = $request->query('token');
        $payerId = $request->query('PayerID');
    
        if (!$paymentId || !$payerId) {
            return response()->json(['error' => 'Invalid PayPal response'], 400);
        }
    
        DB::beginTransaction();
        try {
            $paypal = new PayPalClient;
            $paypal->setApiCredentials(config('paypal'));
            $paypal->getAccessToken();
    
            $captureResponse = $paypal->capturePaymentOrder($paymentId);
    
            // Log the full response for debugging
            Log::info('PayPal Capture Response', ['response' => $captureResponse]);
    
            if (!isset($captureResponse['status'])) {
                throw new \Exception('Invalid PayPal capture response: ' . json_encode($captureResponse));
            }
    
            if ($captureResponse['status'] !== 'COMPLETED') {
                throw new \Exception('PayPal payment not completed: ' . json_encode($captureResponse));
            }
    
            $student = auth()->user();
            $studentRecord = Student::where('user_id', $student->id)->first();
            if (!$studentRecord) {
                throw new \Exception('Student record not found');
            }
    
            $cart = session('cart');
            if (!$cart) {
                throw new \Exception('Cart session expired');
            }
    
            foreach ($cart as $item) {
                $courseId = $item['course_id'];
                $amount = $item['amount'];
                $instructorId = $item['instructor_id'];
    
                Payment::create([
                    'amount' => $amount,
                    'currency' => 'USD',
                    'payment_method' => 'PayPal',
                    'payment_date' => now(),
                    'transaction_code' => $paymentId,
                    'payment_status' => 'completed',
                    'course_id' => $courseId,
                    'student_id' => $studentRecord->id,
                ]);
    
                Enrollment::create([
                    'course_id' => $courseId,
                    'student_id' => $studentRecord->id,
                ]);
            }
    
            DB::commit();
            return response()->json(['message' => 'Payment successful'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayPal Payment Execution Failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    

    public function paypalCancel()
    {
        Log::info('PayPal Payment Canceled');
        return response()->json(['message' => 'Payment canceled']);
    }

}
