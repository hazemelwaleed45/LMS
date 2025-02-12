<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'description',
        'content_url',
        'duration',
        'course_id',
        'file',
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
