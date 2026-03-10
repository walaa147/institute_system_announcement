<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. مسح الذاكرة المؤقتة (Cache) الخاصة بالصلاحيات لتجنب أي تعارض
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. تعريف قائمة الصلاحيات الشاملة التي اتفقنا عليها
        $permissions = [
            // --- صلاحيات مدير النظام (Super Admin) ---
            'view-any-institute', 'create-institute', 'update-institute', 'delete-institute',
            'toggle-institute-status', 'update-institute-financials',
            'view-financial-reports', 'view-system-profits', 'view-institute-profits',
            'create-secretary', 'manage-users',

            // --- صلاحيات سكرتير المعهد (Secretary) ---
            'update-own-institute',
            'view-department', 'create-department', 'update-department', 'delete-department',
            'view-course', 'create-course', 'update-course', 'delete-course',
            'view-diploma', 'create-diploma', 'update-diploma', 'delete-diploma',
            'link-courses-to-diploma',
            'view-advertisement', 'create-advertisement', 'update-advertisement', 'delete-advertisement',
            'view-booking', 'confirm-booking', 'cancel-booking',
            'manage-waiting-list',
            'view-institute-statistics', 'send-notifications',

            // --- صلاحيات الطالب  (Student) ---

            'update-own-profile', 'delete-own-account',
            'toggle-favorite', 'toggle-like',
            'create-booking', 'view-own-bookings', 'join-waiting-list',
        ];

        // 3. إدخال الصلاحيات في قاعدة البيانات (بامان دون تكرار)
        // نستخدم حارس 'web' وهو الافتراضي الذي يعمل بسلاسة مع Sanctum في Laravel
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // 4. إنشاء الأدوار (Roles) وربط كل دور بصلاحياته

        // أ- دور مدير النظام
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdminRole->syncPermissions([
            'view-any-institute', 'create-institute', 'update-institute', 'delete-institute',
            'toggle-institute-status', 'update-institute-financials',
            'view-financial-reports', 'view-system-profits', 'view-institute-profits',
            'create-secretary', 'manage-users'
        ]);

        // ب- دور السكرتير
        $secretaryRole = Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'sanctum']);
        $secretaryRole->syncPermissions([
            'update-own-institute',
            'view-department', 'create-department', 'update-department', 'delete-department',
            'view-course', 'create-course', 'update-course', 'delete-course',
            'view-diploma', 'create-diploma', 'update-diploma', 'delete-diploma',
            'link-courses-to-diploma',
            'view-advertisement', 'create-advertisement', 'update-advertisement', 'delete-advertisement',
            'view-booking', 'confirm-booking', 'cancel-booking',
            'manage-waiting-list',
            'view-institute-statistics', 'send-notifications',
        ]);

        // ج- دور الطالب
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'sanctum']);
        $studentRole->syncPermissions([
            'update-own-profile', 'delete-own-account',
            'toggle-favorite', 'toggle-like',
            'create-booking', 'view-own-bookings', 'join-waiting-list',
        ]);
    }
}
