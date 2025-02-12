<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Page;

class PagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */  
    
     public function run()
    {
            // Clear the table before seeding
            Page::truncate();
    
            // Seed sample data
            $pages = [
                [
                    'page_name' => 'home',
                    'key' => 'header',
                    'value' => '<h1>Welcome to Our Platform</h1>',
                    'json_value' => null,
                ],
                [
                    'page_name' => 'home',
                    'key' => 'intro_text',
                    'value' => 'This is a sample introduction text for the home page.',
                    'json_value' => null,
                ],
                [
                    'page_name' => 'faq',
                    'key' => 'faq_content',
                    'value' => null,
                    'json_value' => json_encode([
                        [
                            'question' => 'What is this platform about?',
                            'answer' => 'This platform provides online courses and learning resources.',
                        ],
                        [
                            'question' => 'How do I sign up?',
                            'answer' => 'You can sign up by clicking the "Register" button on the homepage.',
                        ],
                    ]),
                ],
                [
                    'page_name' => 'about',
                    'key' => 'team',
                    'value' => null,
                    'json_value' => json_encode([
                        [
                            'name' => 'John Doe',
                            'role' => 'CEO',
                        ],
                        [
                            'name' => 'Jane Smith',
                            'role' => 'CTO',
                        ],
                    ]),
                ],
                [
                    'page_name' => 'contact',
                    'key' => 'contact_info',
                    'value' => 'Email: support@example.com | Phone: +1234567890',
                    'json_value' => null,
                ],
            ];
    
            // Insert data into the table
            foreach ($pages as $page) {
                Page::create($page);
            }
        }
    }
