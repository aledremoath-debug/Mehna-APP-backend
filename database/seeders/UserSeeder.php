<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserPassword; // تأكد من وجود الموديل الذي أنشأناه يدوياً

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // تعطيل الرقابة على القيود لتنظيف الجداول
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        UserPassword::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // --- مستخدم مدير (Admin) ---
        $admin = User::create([
            'full_name'   => 'مدير النظام',
            'email'       => 'admin@mahna.com',
            'phone'       => '777000000',
            'user_type'   => 9,
            'location_id' => 1,
        ]);

        // إدخال كلمة المرور في الجدول المنفصل
        UserPassword::create([
            'user_id'       => $admin->user_id,
            'password_hash' => Hash::make('admin123'),
        ]);

        // --- مستخدم عميل (Customer) ---
        $customer = User::create([
            'full_name'   => 'أحمد العميل',
            'email'       => 'customer@test.com',
            'phone'       => '777111111',
            'user_type'   => 0,
            'location_id' => 1,
        ]);

        UserPassword::create([
            'user_id'       => $customer->user_id,
            'password_hash' => Hash::make('password123'),
        ]);
    }
}