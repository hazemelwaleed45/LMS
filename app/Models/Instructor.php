<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'about',
        'gender',
        'date_of_birth',
        'image',
        'phone',
        'paypal_account',
        'user_id',
        'major',
    ];

    protected static function boot()
    {
        parent::boot();

        // Invalidate cache when an instructor is saved (created or updated)
        static::saved(function () {
            Cache::forget('instructors_list');
        });

        // Invalidate cache when an instructor is deleted
        static::deleted(function () {
            Cache::forget('instructors_list');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
