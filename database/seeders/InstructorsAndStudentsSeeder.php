<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstructorsAndStudentsSeeder extends Seeder
{
    public function run()
    {
        $instructors = [
            [
                'name' => 'Omar Sehly',
                'about' => 'Experienced math instructor.',
                'gender' => 'male',
                'date_of_birth' => '1999-04-25', // Corrected format
                'image' => 'omaaarr.jpg',
                'phone' => '01107584784',
                'paypal_account' => 'omarSehly@paypal.com',
                'user_id' => 2,
                'major' => 'university',
            ],
            [
                'name' => 'Mohamed Ibrahim',
                'about' => 'Physics expert.',
                'gender' => 'male',
                'date_of_birth' => '2000-07-17', // Corrected format
                'image' => 'mo_ibraheem.jpg',
                'phone' => '+987654321',
                'paypal_account' => 'moIbrahim@paypal.com',
                'user_id' => 3,
                'major' => 'university',
            ],
        ];

        $students = [
            [
                'first_name' => 'Amira',
                'last_name' => 'Alaa',
                'username' => 'amira_alaa',
                'date_of_birth' => '2002-04-20', // Corrected format
                'image' => 'amira_alla.jpg',
                'education' => 'university',
                'gender' => 'female',
                'country' => 'Egypt',
                'phone' => '01001102255',
                'user_id' => 4,
            ],
            [
                'first_name' => 'Noha',
                'last_name' => 'Ahmed',
                'username' => 'nohaAhmed',
                'date_of_birth' => '2000-03-12', // Corrected format
                'image' => 'nohannnaa.jpg',
                'education' => 'university',
                'gender' => 'female',
                'country' => 'Egypt',
                'phone' => '01245789630',
                'user_id' => 5,
            ],
            [
                'first_name' => 'Mahmoud',
                'last_name' => 'Ali',
                'username' => 'moAli',
                'date_of_birth' => '2000-03-12', // Corrected format
                'image' => 'mohmoudAli.jpg',
                'education' => 'university',
                'gender' => 'male',
                'country' => 'Egypt',
                'phone' => '01145527896',
                'user_id' => 6,
            ],
        ];

        // Insert instructors
        foreach ($instructors as $instructor) {
            DB::table('instructors')->insert(array_merge($instructor, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Insert students
        foreach ($students as $student) {
            DB::table('students')->insert(array_merge($student, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
