<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Clear the enrollment table
        DB::table('enrollment')->truncate();

       
                // Get all payments where payment_status is 'completed'
                $payments = DB::table('payments')
                ->where('payment_status', 'completed')
                ->get();

        // Loop through each payment and create an enrollment record
        foreach ($payments as $payment) {
            // Check if the enrollment already exists to avoid duplicates
            $exists = DB::table('enrollment')
                ->where('course_id', $payment->course_id)
                ->where('student_id', $payment->student_id)
                ->exists();

            if (!$exists) {
                DB::table('enrollment')->insert([
                    'course_id' => $payment->course_id,
                    'student_id' => $payment->student_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}