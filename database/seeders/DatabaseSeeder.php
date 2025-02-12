<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // Call individual seeders here
        $this->call([
            // UsersTableSeeder::class,
            // InstructorsAndStudentsSeeder::class,
            PagesTableSeeder::class,
            // CategoriesSeeder::class,
            // CoursesSeeder::class,
            // EnrollmentTableSeeder::class,
            // PaymentsSeeder::class,

            // Add more seeders as needed
        ]);
    }
}
