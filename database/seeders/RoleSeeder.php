<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Employee;
use App\Models\Institute; // أضفنا موديل المعهد
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إنشاء الصلاحيات الشاملة
        $permissions = [
            // المعاهد (جديد)
            'institute.manage', 'institute.create', 'institute.edit', 'institute.delete', 'institute.view',

            // الأقسام (جديد)
            'department.manage', 'department.create', 'department.edit', 'department.delete', 'department.view',

            // الكورسات
            'course.manage', 'course.create', 'course.edit', 'course.delete', 'course.view',

            // الدبلومات
            'diploma.manage', 'diploma.create', 'diploma.edit', 'diploma.delete', 'diploma.view',

            // الحجوزات
            'booking.manage', 'booking.view', 'booking.complete',

            // المفضلة
            'like.toggle',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. إنشاء الأدوار
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $secretary = Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        // 3. منح الصلاحيات (الآدمن والسكرتير يملكون كل شيء)
        $admin->syncPermissions($permissions);
        $secretary->syncPermissions($permissions);

        $student->givePermissionTo(['like.toggle']);
        $customer->givePermissionTo(['like.toggle']);

        // 4. إنشاء معهد افتراضي (لتجنب خطأ الـ Foreign Key في جدول الموظفين)
        $defaultInstitute = Institute::firstOrCreate(
            ['name_ar' => 'المعهد الرئيسي'],
            [
                'name_en' => 'Main Institute',
                'description' => 'المعهد الافتراضي للنظام',
            ]
        );

        // 5. إنشاء مستخدم سكرتير
        $user = User::firstOrCreate(
            ['email' => 'secretary@example.com'],
            [
                'name' => 'Secretary User',
                'password' => Hash::make('12345678'),
            ]
        );

        $user->assignRole($secretary);

        // 6. إنشاء سجل الموظف وربطه بالمعهد الافتراضي
        Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'code' => 'EMP001',
                'name_ar' => 'سكرتير النظام',
                'institute_id' => $defaultInstitute->id, // تم الربط بالـ ID الحقيقي
                'name_en' => 'System Secretary',
                'job_title' => 'Secretary',
                'is_active' => true,
            ]
        );

        $this->command->info('تم تحديث الأدوار، الصلاحيات، وإنشاء المعهد والموظف بنجاح!');
    }
}
