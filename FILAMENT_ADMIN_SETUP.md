# Filament Admin Panel with Spatie Permissions Setup

## Overview
This document outlines the configuration of the Filament Admin Panel to use the `Admins` model with Spatie permissions package.

## Changes Made

### 1. Installed Spatie Permissions Package
```bash
composer require spatie/laravel-permission
```

### 2. Updated Auth Configuration (`config/auth.php`)
- Added `admin` guard that uses the `admins` provider
- Added `admins` provider that uses the `Admins` model
- Added password reset configuration for admins

### 3. Updated Admins Model (`app/Models/Admins.php`)
- Added `HasRoles` trait from Spatie permissions
- Specified `users` table as the model's table
- Removed unused `TwoFactorAuthenticatable` trait
- Model already implements `FilamentUser` and `HasAvatar` interfaces
- Contains `canAccessPanel()` method that checks for required roles

### 4. Updated AdminPanelProvider (`app/Providers/Filament/AdminPanelProvider.php`)
- Added `authGuard('admin')` to use the admin guard
- Added imports for Spatie permission middleware (for future use)

### 5. Created Roles and Permissions Seeder
- Created `AdminRolesAndPermissionsSeeder` with the following roles:
  - `super_admin`: Full access to all features
  - `admin`: General administrative access
  - `cashier`: Transaction management access
  - `panel_user`: Basic panel access
  - `IT-head-dept`: IT department head access
  - `BA-head-dept`: Business Administration department head access
  - `HM-head-dept`: Hotel Management department head access
  - `registrar`: Student enrollment and records access

### 6. Permissions Created
- `view_admin_panel`: Basic panel access
- `manage_users`: User management
- `manage_students`: Student management
- `manage_faculty`: Faculty management
- `manage_courses`: Course management
- `manage_classes`: Class management
- `manage_enrollments`: Enrollment management
- `manage_transactions`: Transaction management
- `manage_settings`: System settings
- `view_reports`: Report viewing
- `manage_notifications`: Notification management
- `view_IT_students_students`: IT students access
- `view_BA_students_students`: BA students access
- `view_HM_students_students`: HM students access

## Usage

### Creating Admin Users
```php
use App\Models\Admins;
use Spatie\Permission\Models\Role;

// Create admin user
$admin = Admins::create([
    'name' => 'Admin Name',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
]);

// Assign role
$admin->assignRole('super_admin');
```

### Checking Permissions in Code
```php
// Check if user has role
if ($admin->hasRole('super_admin')) {
    // User is super admin
}

// Check if user has permission
if ($admin->can('manage_users')) {
    // User can manage users
}

// Check if user has any of multiple roles
if ($admin->hasAnyRole(['admin', 'super_admin'])) {
    // User has admin or super admin role
}
```

### Panel Access Control
The `canAccessPanel()` method in the `Admins` model controls access:
- For 'admin' panel: Requires one of: `cashier`, `panel_user`, `admin`, `super_admin`
- For 'faculty' panel: Requires `faculty` role (if enabled in config)
- Default: Requires `super_admin` role

## Test Admin User
A test admin user has been created:
- Email: `admin@test.com`
- Password: `password`
- Role: `super_admin`

## Running the Setup
1. Run the seeder to create roles and permissions:
```bash
php artisan db:seed --class=AdminRolesAndPermissionsSeeder
```

2. Clear config cache:
```bash
php artisan config:clear
```

3. Access the admin panel at `/admin` and login with admin credentials.

## Notes
- The `Admins` model uses the `users` table
- All permissions are created with `admin` guard
- The existing permission tables were already present in the database
- The model includes methods for checking department-specific permissions and viewable courses
