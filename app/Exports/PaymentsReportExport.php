<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentsReportExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return DB::table('payments')
            ->join('students', 'payments.student_id', '=', 'students.id')
            ->join('enrollment', function ($join) {
                $join->on('enrollment.course_id', '=', 'payments.course_id')
                     ->on('enrollment.student_id', '=', 'payments.student_id');
            })
            ->join('courses', 'payments.course_id', '=', 'courses.id')
            ->join('instructors', 'courses.instructor_id', '=', 'instructors.id')
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
    }

    public function headings(): array
    {
        return [
            'Payment ID',
            'Student Name',
            'Instructor Name',
            'Course Name',
            'Amount Paid',
            'Payment Method'
        ];
    }
}
