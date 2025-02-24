<?php

namespace App\Providers;
use App\Models\Course;
use App\Models\Lecture;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {

        Route::bind('lecture', function ($value, $route) {
            return Lecture::where('id', $value)
                ->where('course_id', $route->parameter('course'))
                ->firstOrFail();
        });
    }
}