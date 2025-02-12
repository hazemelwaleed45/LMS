<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentsSeeder extends Seeder
{
    public function run()
    {
        $payments = [
            [
                'amount' => 200.00,
                'payment_method' => 'visa',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-01',
                'payment_status' => 'completed',
                'course_id' => 2,
                'student_id' => 1,
            ],
            [
                'amount' => 200.00,
                'payment_method' => 'PayPal',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-02',
                'payment_status' => 'completed',
                'course_id' => 3,
                'student_id' => 2,
            ],
            [
                'amount' => 300.00,
                'payment_method' => 'Mastercard',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-03',
                'payment_status' => 'pending',
                'course_id' => 5,
                'student_id' => 3,
            ],
            [
                'amount' => 300.00,
                'payment_method' => 'visa',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-04',
                'payment_status' => 'completed',
                'course_id' => 5,
                'student_id' => 1,
            ],
            [
                'amount' => 300.00,
                'payment_method' => 'PayPal',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-05',
                'payment_status' => 'completed',
                'course_id' => 5,
                'student_id' => 2,
            ],
            [
                'amount' => 220.00,
                'payment_method' => 'visa',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-06',
                'payment_status' => 'completed',
                'course_id' => 4,
                'student_id' => 2,
            ],
            [
                'amount' => 180.00,
                'payment_method' => 'Mastercard',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-07',
                'payment_status' => 'completed',
                'course_id' => 3,
                'student_id' => 3,
            ],
            [
                'amount' => 150.00,
                'payment_method' => 'PayPal',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-08',
                'payment_status' => 'pending',
                'course_id' => 2,
                'student_id' => 3,
            ],
            [
                'amount' => 180.00,
                'payment_method' => 'visa',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-09',
                'payment_status' => 'completed',
                'course_id' => 6,
                'student_id' => 2,
            ],
            [
                'amount' => 220.00,
                'payment_method' => 'Mastercard',
                'transaction_code' => strtoupper(Str::random(10)),
                'payment_date' => '2025-01-10',
                'payment_status' => 'completed',
                'course_id' => 4,
                'student_id' => 1,
            ],
        ];

        // Insert payments into the database
        foreach ($payments as $payment) {
            DB::table('payments')->insert(array_merge($payment, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
