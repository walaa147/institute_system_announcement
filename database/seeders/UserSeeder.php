<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. إنشاء حساب مدير النظام (Super Admin)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@app.com'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password123'),
            ]
        );
        // إسناد صلاحية المدير العام له
        $superAdmin->assignRole('super_admin');

        // 2. إنشاء حساب طالب تجريبي (Student)
        $student = User::firstOrCreate(
            ['email' => 'student@app.com'],
            [
                'name' => 'طالب تجريبي',
                'password' => Hash::make('password123'),
            ]
        );
        // إسناد صلاحية الطالب له
        $student->assignRole('student');
    }
}
