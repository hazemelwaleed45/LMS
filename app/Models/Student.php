<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'date_of_birth',
        'image',
        'education',
        'gender',
        'country',
        'phone',
        'user_id',
        'interests',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'enrollment', 'student_id', 'course_id');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
}
