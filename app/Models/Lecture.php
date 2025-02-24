<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'lecture_attachments',
        'duration',
        'course_id',
    ];
    protected $casts = [
        'lecture_attachments' => 'array',
    ];
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
