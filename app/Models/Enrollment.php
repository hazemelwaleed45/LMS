<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'enrollment';

    protected $fillable = [
        'course_id',
        'student_id',
        'created_at',
        'updated_at',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the course associated with the enrollment.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
