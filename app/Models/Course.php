<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'price',
        'duration',
        'start_date',
        'end_date',
        'rate',
        'image',
        'admin_amount',
        'instructor_amount',
        'category_id',
        'instructor_id',
        'major',
        'prerequister',
        'semster',
        'course_level',
        'introduction_vedio_path'
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function lectures()
    {
        return $this->hasMany(Lecture::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }
    public function remove(): bool
    {
        return $this->delete();
        $this->lectures()->delete();
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function getRatingMetrics()
    {
        // Get all reviews for this course
        $reviews = $this->reviews;

        // Calculate total reviews
        $totalReviews = $reviews->count();

        // Calculate average rating
        $averageRating = $reviews->avg('rating');

        // Calculate rating percentages
        $ratingCounts = $reviews->groupBy('rating')->map->count();
        $ratingPercentages = [];

        for ($i = 1; $i <= 5; $i++) {
            $count = $ratingCounts->get($i, 0);
            $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100, 2) : 0;
            $ratingPercentages[$i] = $percentage . '%';
        }

        return [
            'average_rating' => round($averageRating, 1), // Round to 1 decimal place
            'total_reviews' => $totalReviews,
            'ratingPercentages' => $ratingPercentages,
        ];
    }
}
