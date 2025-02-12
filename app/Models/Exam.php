<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;


    protected $fillable = [
        'id',
        'date',
        'grade',
        'assignment_id',
        'student_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}
