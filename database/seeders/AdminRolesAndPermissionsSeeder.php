<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view_admin_panel',
            'manage_users',
            'manage_students',
            'manage_faculty',
            'manage_courses',
            'manage_classes',
            'manage_enrollments',
            'manage_transactions',
            'manage_settings',
            'view_reports',
            'manage_notifications',
            'view_IT_students_students',
            'view_BA_students_students',
            'view_HM_students_students',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }

        // Create roles
        $roles = [
            'super_admin' => $permissions, // Super admin gets all permissions
            'admin' => [
                'view_admin_panel',
                'manage_users',
                'manage_students',
                'manage_faculty',
                'manage_courses',
                'manage_classes',
                'manage_enrollments',
                'view_reports',
                'manage_notifications',
                'view_IT_students_students',
                'view_BA_students_students',
                'view_HM_students_students',
            ],
            'cashier' => [
                'view_admin_panel',
                'manage_transactions',
                'view_reports',
            ],
            'panel_user' => [
                'view_admin_panel',
            ],
            'IT-head-dept' => [
                'view_admin_panel',
                'manage_students',
                'manage_classes',
                'view_reports',
                'view_IT_students_students',
            ],
            'BA-head-dept' => [
                'view_admin_panel',
                'manage_students',
                'manage_classes',
                'view_reports',
                'view_BA_students_students',
            ],
            'HM-head-dept' => [
                'view_admin_panel',
                'manage_students',
                'manage_classes',
                'view_reports',
                'view_HM_students_students',
            ],
            'registrar' => [
                'view_admin_panel',
                'manage_students',
                'manage_enrollments',
                'view_reports',
                'view_IT_students_students',
                'view_BA_students_students',
                'view_HM_students_students',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'admin']);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('Admin roles and permissions created successfully!');
    }
}
