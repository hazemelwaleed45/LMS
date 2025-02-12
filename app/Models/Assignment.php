<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'title',
        'grade',
        'duration',
        'content',
        'lecture_id',
    ];

    public function lecture()
    {
        return $this->belongsTo(Lecture::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
