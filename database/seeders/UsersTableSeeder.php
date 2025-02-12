<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'email' => 'hazem@gmail.com',
                'password' => Hash::make('123456789'),
                'role' => 'admin',
            ],
            [
                'email' => 'omar@gmail.com',
                'password' => Hash::make('123456789'),
                'role' => 'instructor',
            ],
            [
                'email' => 'mohamed@gmail.com',
                'password' => Hash::make('123456789'),
                'role' => 'instructor',
            ],
            [
                'email' => 'amira@gmail.com',
                'password' => Hash::make('123456789'),
                'role' => 'student',
            ],
            [
                'email' => 'noha@gmial.com',
                'password' => Hash::make('123456789'),
                'role' => 'student',
            ],
            [
                'email' => 'mahmoud@gmail.com',
                'password' => Hash::make('123456789'),
                'role' => 'student',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert(array_merge($user, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}