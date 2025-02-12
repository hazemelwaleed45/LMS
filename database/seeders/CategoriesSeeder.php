<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Mathematics', 'description' => 'Courses about mathematics.'],
            ['name' => 'Physics', 'description' => 'Courses about physics.'],
            ['name' => 'Computer Science', 'description' => 'Courses about computer science.'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
