<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@almonajah.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create Editor User
        User::create([
            'name' => 'محرر',
            'email' => 'editor@almonajah.com',
            'password' => Hash::make('password'),
            'role' => 'editor',
        ]);

        // Create Sample Categories
        Category::create([
            'name' => 'تقنية',
            'slug' => 'technology',
            'description' => 'مقالات تقنية ومعلوماتية',
            'color' => '#3b82f6',
        ]);

        Category::create([
            'name' => 'تعليم',
            'slug' => 'education',
            'description' => 'مقالات تعليمية',
            'color' => '#10b981',
        ]);

        Category::create([
            'name' => 'أخبار',
            'slug' => 'news',
            'description' => 'الأخبار والأحداث',
            'color' => '#f59e0b',
        ]);
    }
}


