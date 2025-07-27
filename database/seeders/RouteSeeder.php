<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\User;
use App\Enums\RouteStatus;
use App\Enums\RouteType;

use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::where('role', 'admin')->first();
        $createdBy = $adminUser?->id;

        $routes = [
            // Public Routes
            [
                'name' => 'welcome',
                'display_name' => 'Welcome',
                'path' => '/',
                'type' => RouteType::PUBLIC,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Landing page for the application',
                'icon' => 'heroicon-o-home',
                'is_navigation' => false,
                'sort_order' => 0,
            ],
            [
                'name' => 'enroll',
                'display_name' => 'Online Enrollment',
                'path' => '/enroll',
                'type' => RouteType::ENROLLMENT,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Student enrollment form',
                'icon' => 'heroicon-o-clipboard-document-list',
                'is_navigation' => true,
                'sort_order' => 1,
            ],

            // Student Routes
            [
                'name' => 'dashboard',
                'display_name' => 'Student Dashboard',
                'path' => '/dashboard',
                'type' => RouteType::STUDENT,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Main dashboard for students',
                'icon' => 'heroicon-o-squares-2x2',
                'is_navigation' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'schedule.index',
                'display_name' => 'Schedule',
                'path' => '/schedule',
                'type' => RouteType::STUDENT,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Student class schedule',
                'icon' => 'heroicon-o-calendar',
                'is_navigation' => true,
                'sort_order' => 11,
            ],
            [
                'name' => 'tuition.index',
                'display_name' => 'Tuition',
                'path' => '/tuition',
                'type' => RouteType::STUDENT,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Tuition fees and payments',
                'icon' => 'heroicon-o-banknote',
                'is_navigation' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'subjects.index',
                'display_name' => 'Subjects',
                'path' => '/subjects',
                'type' => RouteType::STUDENT,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Enrolled subjects and courses',
                'icon' => 'heroicon-o-book-open',
                'is_navigation' => true,
                'sort_order' => 13,
            ],

            // Faculty Routes
            [
                'name' => 'faculty.dashboard',
                'display_name' => 'Faculty Dashboard',
                'path' => '/faculty/dashboard',
                'type' => RouteType::FACULTY,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Main dashboard for faculty members',
                'icon' => 'heroicon-o-squares-2x2',
                'is_navigation' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'faculty.classes.index',
                'display_name' => 'My Classes',
                'path' => '/faculty/classes',
                'type' => RouteType::FACULTY,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Faculty class management',
                'icon' => 'heroicon-o-academic-cap',
                'is_navigation' => true,
                'sort_order' => 21,
            ],
            [
                'name' => 'faculty.students.index',
                'display_name' => 'Students',
                'path' => '/faculty/students',
                'type' => RouteType::FACULTY,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Student management for faculty',
                'icon' => 'heroicon-o-users',
                'is_navigation' => true,
                'sort_order' => 22,
            ],
            [
                'name' => 'faculty.schedule.index',
                'display_name' => 'Schedule',
                'path' => '/faculty/schedule',
                'type' => RouteType::FACULTY,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Faculty teaching schedule',
                'icon' => 'heroicon-o-calendar',
                'is_navigation' => true,
                'sort_order' => 23,
            ],

            // Development/Future Features
            [
                'name' => 'faculty.attendance',
                'display_name' => 'Attendance',
                'path' => '/faculty/attendance',
                'type' => RouteType::FACULTY,
                'status' => RouteStatus::DEVELOPMENT,
                'description' => 'Student attendance tracking',
                'icon' => 'heroicon-o-clipboard-document-list',
                'is_navigation' => true,
                'sort_order' => 24,
                'development_message' => 'Attendance tracking feature is currently in development. Stay tuned for updates!',
            ],
            [
                'name' => 'faculty.grades',
                'display_name' => 'Grades',
                'path' => '/faculty/grades',
                'type' => RouteType::FACULTY,
                'status' => RouteStatus::DEVELOPMENT,
                'description' => 'Grade management system',
                'icon' => 'heroicon-o-chart-bar',
                'is_navigation' => true,
                'sort_order' => 25,
                'development_message' => 'Grade management system is coming soon with advanced features!',
            ],
            [
                'name' => 'faculty.assignments',
                'display_name' => 'Assignments',
                'path' => '/faculty/assignments',
                'type' => RouteType::FACULTY,
                'status' => RouteStatus::DEVELOPMENT,
                'description' => 'Assignment creation and management',
                'icon' => 'heroicon-o-document-text',
                'is_navigation' => true,
                'sort_order' => 26,
                'development_message' => 'Assignment management feature is under development.',
            ],

            // Utility Routes
            [
                'name' => 'changelog.index',
                'display_name' => 'Change Log',
                'path' => '/changelog',
                'type' => RouteType::PUBLIC,
                'status' => RouteStatus::ACTIVE,
                'description' => 'Application change log and updates',
                'icon' => 'heroicon-o-document-text',
                'is_navigation' => true,
                'sort_order' => 100,
            ],
        ];

        foreach ($routes as $routeData) {
            $routeData['created_by'] = $createdBy;
            $routeData['updated_by'] = $createdBy;

            Route::updateOrCreate(
                ['name' => $routeData['name']],
                $routeData
            );
        }
    }
}
