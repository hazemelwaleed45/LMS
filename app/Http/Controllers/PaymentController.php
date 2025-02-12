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

        // Ensure the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
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
    
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart' => 'required|array|min:1',
            'cart.*.course_id' => 'required|exists:courses,id',
            'cart.*.amount' => 'required|numeric|min:1',
            'cart.*.instructor_id' => 'required|exists:instructors,id',
            'payment_method' => 'required|in:visa,Mastercard',
            'card_number' => 'required|digits_between:13,19',
            'card_exp_month' => 'required|numeric|min:1|max:12',
            'card_exp_year' => 'required|numeric|min:' . date('Y'),
            'card_cvc' => 'required|digits:3',
        ]);
    
        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        // Check if card number is valid using Luhn algorithm
        if (!$this->isValidCardNumber($request->card_number)) {
            return response()->json(['error' => 'Invalid card number'], 400);
        }
    
        $cart = $request->cart;
        $paymentMethod = $request->payment_method;
        $student = $request->user();
    
        // Ensure the user is authenticated and linked to a student record
        if (!$student) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    
        $studentRecord = Student::where('user_id', $student->id)->first();
        if (!$studentRecord) {
            return response()->json(['error' => 'Student record not found for the authenticated user'], 404);
        }
    
        DB::beginTransaction();
        try {
            $totalAmount = 0;
    
            // Calculate total amount for the cart
            foreach ($cart as $item) {
                $totalAmount += $item['amount'];
            }
    
            // Convert total amount to cents for Stripe (e.g., $10.50 becomes 1050)
            $totalAmountCents = $totalAmount * 100;
    
            // Initialize Stripe with the secret key
            Stripe::setApiKey(env('STRIPE_SECRET'));
    
            // Create a PaymentIntent to process the payment
            $paymentIntent = PaymentIntent::create([
                'amount' => $totalAmountCents,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'payment_method_data' => [
                    'type' => 'card',
                    'card' => [
                        'number' => $request->card_number,
                        'exp_month' => $request->card_exp_month,
                        'exp_year' => $request->card_exp_year,
                        'cvc' => $request->card_cvc,
                    ],
                ],
                'confirmation_method' => 'automatic',
                'confirm' => true,
            ]);
    
            // Check if the payment was successful
            if ($paymentIntent->status !== 'succeeded') {
                throw new Exception('Payment failed: ' . $paymentIntent->last_payment_error->message);
            }
    
            // Process cart items and save payment records
            foreach ($cart as $item) {
                $courseId = $item['course_id'];
                $amount = $item['amount'];
                $instructorId = $item['instructor_id'];
    
                // Validate that the instructor matches the course
                $course = Course::find($courseId);
                if (!$course || $course->instructor_id != $instructorId) {
                    throw new \Exception("Instructor ID $instructorId does not match course ID $courseId");
                }
    
                $adminCommission = PlatformSetting::getAdminCommission(); // Admin commission percentage
                $adminShare = ($amount * $adminCommission) / 100;
                $instructorShare = $amount - $adminShare;
    
                $instructor = Instructor::find($instructorId);
                if (!$instructor) {
                    throw new \Exception("Instructor not found for ID $instructorId");
                }
    
                // Record Payment
                Payment::create([
                    'amount' => $amount,
                    'currency' => 'USD',
                    'payment_method' => $paymentMethod,
                    'payment_date' => now(),
                    'transaction_code' => $paymentIntent->id, // Use Stripe's PaymentIntent ID
                    'payment_status' => 'completed',
                    'course_id' => $courseId,
                    'student_id' => $studentRecord->id,
                ]);
    
                // Record Enrollment
                Enrollment::create([
                    'course_id' => $courseId,
                    'student_id' => $studentRecord->id,
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Payment processed successfully!',
                'total_amount' => $totalAmount,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Helper: Validate Card Number (Luhn Algorithm)
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
            $totalAmount = 0;
            foreach ($cart as $item) {
                $totalAmount += $item['amount'];
            }

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

            if (!isset($response['id'])) {
                throw new \Exception('Failed to create PayPal order');
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
            Log::error('PayPal Payment Error: ' . $e->getMessage());
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

            if (!isset($captureResponse['status']) || $captureResponse['status'] !== 'COMPLETED') {
                throw new \Exception('PayPal payment capture failed');
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
        return response()->json(['message' => 'Payment canceled']);
    }

}
