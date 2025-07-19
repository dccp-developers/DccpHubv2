<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Faculty;
// use App\Models\Classes;
// use App\Models\Schedule;
use App\Models\GeneralSettings;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

final class FacultyDashboardController extends Controller
{
    /**
     * Main faculty dashboard action
     */
    public function __invoke(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Ensure the user is a faculty member
        if (!$user->isFaculty()) {
            abort(403, 'Only faculty members can access this dashboard.');
        }

        /** @var Faculty $faculty */
        $faculty = $user->faculty;

        if (!$faculty) {
            abort(404, 'Faculty record not found.');
        }

        // Get general settings
        $generalSettings = GeneralSettings::first();
        
        // Get current semester and school year
        $settings = $this->getSettings();
        $currentSemester = $settings['semester'];
        $currentSchoolYear = $settings['schoolYear'];

        // Get faculty's classes and schedules
        $classesData = $this->getClassesData($faculty, $currentSemester, $currentSchoolYear);
        $scheduleData = $this->getScheduleData($faculty, $currentSemester, $currentSchoolYear);
        
        // Calculate stats for the dashboard
        $statsData = $this->calculateStats($faculty, $classesData, $scheduleData);

        // Prepare faculty data
        $facultyData = [
            'name' => $faculty->first_name . ' ' . $faculty->last_name,
            'department' => $faculty->department ?? 'Computer Science',
            'email' => $faculty->email,
            'office_hours' => $faculty->office_hours ?? 'Mon-Fri 2:00-4:00 PM',
            'photo_url' => $faculty->photo_url ?? null,
        ];

        // Get today's schedule
        $todaysSchedule = $this->getTodaysSchedule($faculty);

        // Get recent activities (placeholder for now)
        $recentActivities = $this->getRecentActivities($faculty);

        return Inertia::render('Faculty/Dashboard', [
            'faculty' => $facultyData,
            'stats' => $statsData,
            'classes' => $classesData,
            'todaysSchedule' => $todaysSchedule,
            'recentActivities' => $recentActivities,
            'user' => $user,
            'semester' => $currentSemester,
            'schoolYear' => $currentSchoolYear,
            'generalSettings' => $generalSettings,
        ]);
    }

    /**
     * Get application settings
     */
    private function getSettings(): array
    {
        $settings = GeneralSettings::query()->first();
        return [
            'semester' => $settings->semester,
            'schoolYear' => $settings->getSchoolYear(),
        ];
    }

    /**
     * Get classes data for the faculty
     */
    private function getClassesData(Faculty $faculty, int $currentSemester, string $currentSchoolYear): array
    {
        // For now, return sample data since we don't have the exact table structure
        // TODO: Replace with actual database queries once the relationships are confirmed

        return [
            [
                'id' => 1,
                'subject_code' => 'CS101',
                'subject_title' => 'Introduction to Computer Science',
                'section' => 'A',
                'schedule' => 'MWF 8:00-9:30 AM',
                'room' => '201',
                'student_count' => 35,
                'students' => $this->generateSampleStudents(35),
                'color' => 'blue-500',
            ],
            [
                'id' => 2,
                'subject_code' => 'CS102',
                'subject_title' => 'Data Structures and Algorithms',
                'section' => 'A',
                'schedule' => 'TTH 2:00-3:30 PM',
                'room' => '301',
                'student_count' => 28,
                'students' => $this->generateSampleStudents(28),
                'color' => 'purple-500',
            ],
            [
                'id' => 3,
                'subject_code' => 'MATH201',
                'subject_title' => 'Calculus II',
                'section' => 'B',
                'schedule' => 'MWF 10:00-11:30 AM',
                'room' => '105',
                'student_count' => 42,
                'students' => $this->generateSampleStudents(42),
                'color' => 'green-500',
            ],
            [
                'id' => 4,
                'subject_code' => 'CS201',
                'subject_title' => 'Database Systems',
                'section' => 'C',
                'schedule' => 'TTH 10:00-11:30 AM',
                'room' => '205',
                'student_count' => 25,
                'students' => $this->generateSampleStudents(25),
                'color' => 'amber-500',
            ],
            [
                'id' => 5,
                'subject_code' => 'CS301',
                'subject_title' => 'Software Engineering',
                'section' => 'A',
                'schedule' => 'MWF 1:00-2:30 PM',
                'room' => '302',
                'student_count' => 30,
                'students' => $this->generateSampleStudents(30),
                'color' => 'rose-500',
            ],
            [
                'id' => 6,
                'subject_code' => 'CS401',
                'subject_title' => 'Machine Learning',
                'section' => 'A',
                'schedule' => 'TTH 3:30-5:00 PM',
                'room' => '401',
                'student_count' => 20,
                'students' => $this->generateSampleStudents(20),
                'color' => 'indigo-500',
            ],
        ];
    }

    /**
     * Get schedule data for the faculty
     */
    private function getScheduleData(Faculty $faculty, int $currentSemester, string $currentSchoolYear): array
    {
        // For now, return sample data
        // TODO: Replace with actual database queries

        return [
            [
                'id' => 1,
                'day' => 'Monday',
                'start_time' => '08:00',
                'end_time' => '09:30',
                'subject_code' => 'CS101',
                'subject_title' => 'Introduction to Computer Science',
                'room' => '201',
                'section' => 'A',
            ],
            [
                'id' => 2,
                'day' => 'Monday',
                'start_time' => '10:00',
                'end_time' => '11:30',
                'subject_code' => 'MATH201',
                'subject_title' => 'Calculus II',
                'room' => '105',
                'section' => 'B',
            ],
            [
                'id' => 3,
                'day' => 'Monday',
                'start_time' => '13:00',
                'end_time' => '14:30',
                'subject_code' => 'CS301',
                'subject_title' => 'Software Engineering',
                'room' => '302',
                'section' => 'A',
            ],
            // Add more sample schedules for other days
            [
                'id' => 4,
                'day' => 'Tuesday',
                'start_time' => '14:00',
                'end_time' => '15:30',
                'subject_code' => 'CS102',
                'subject_title' => 'Data Structures',
                'room' => '301',
                'section' => 'A',
            ],
            [
                'id' => 5,
                'day' => 'Tuesday',
                'start_time' => '10:00',
                'end_time' => '11:30',
                'subject_code' => 'CS201',
                'subject_title' => 'Database Systems',
                'room' => '205',
                'section' => 'C',
            ],
        ];
    }

    /**
     * Calculate stats for the dashboard
     */
    private function calculateStats(Faculty $faculty, array $classesData, array $scheduleData): array
    {
        $totalClasses = count($classesData);
        $totalStudents = array_sum(array_column($classesData, 'student_count'));
        $totalSchedules = count($scheduleData);
        
        // Calculate average class size
        $averageClassSize = $totalClasses > 0 ? round($totalStudents / $totalClasses, 1) : 0;

        return [
            ['label' => 'Total Classes', 'value' => $totalClasses, 'description' => 'Classes you are teaching this semester'],
            ['label' => 'Total Students', 'value' => $totalStudents, 'description' => 'Students enrolled in your classes'],
            ['label' => 'Weekly Schedules', 'value' => $totalSchedules, 'description' => 'Your weekly class schedules'],
            ['label' => 'Avg. Class Size', 'value' => $averageClassSize, 'description' => 'Average students per class'],
        ];
    }

    /**
     * Get today's schedule for the faculty
     */
    private function getTodaysSchedule(Faculty $faculty): array
    {
        $today = now()->format('l'); // Get day name (Monday, Tuesday, etc.)

        // Sample today's schedule based on current day
        $allSchedules = [
            'Monday' => [
                [
                    'id' => 1,
                    'start_time' => '08:00',
                    'end_time' => '09:30',
                    'subject_code' => 'CS101',
                    'subject_title' => 'Introduction to Computer Science',
                    'room' => '201',
                    'section' => 'A',
                    'color' => 'blue-500',
                ],
                [
                    'id' => 2,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'subject_code' => 'MATH201',
                    'subject_title' => 'Calculus II',
                    'room' => '105',
                    'section' => 'B',
                    'color' => 'green-500',
                ],
                [
                    'id' => 3,
                    'start_time' => '13:00',
                    'end_time' => '14:30',
                    'subject_code' => 'CS301',
                    'subject_title' => 'Software Engineering',
                    'room' => '302',
                    'section' => 'A',
                    'color' => 'rose-500',
                ],
            ],
            'Tuesday' => [
                [
                    'id' => 4,
                    'start_time' => '14:00',
                    'end_time' => '15:30',
                    'subject_code' => 'CS102',
                    'subject_title' => 'Data Structures',
                    'room' => '301',
                    'section' => 'A',
                    'color' => 'purple-500',
                ],
                [
                    'id' => 5,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'subject_code' => 'CS201',
                    'subject_title' => 'Database Systems',
                    'room' => '205',
                    'section' => 'C',
                    'color' => 'amber-500',
                ],
                [
                    'id' => 6,
                    'start_time' => '15:30',
                    'end_time' => '17:00',
                    'subject_code' => 'CS401',
                    'subject_title' => 'Machine Learning',
                    'room' => '401',
                    'section' => 'A',
                    'color' => 'indigo-500',
                ],
            ],
            'Wednesday' => [
                [
                    'id' => 7,
                    'start_time' => '08:00',
                    'end_time' => '09:30',
                    'subject_code' => 'CS101',
                    'subject_title' => 'Introduction to Computer Science',
                    'room' => '201',
                    'section' => 'A',
                    'color' => 'blue-500',
                ],
                [
                    'id' => 8,
                    'start_time' => '10:00',
                    'end_time' => '11:30',
                    'subject_code' => 'MATH201',
                    'subject_title' => 'Calculus II',
                    'room' => '105',
                    'section' => 'B',
                    'color' => 'green-500',
                ],
            ],
        ];

        return $allSchedules[$today] ?? [];
    }

    /**
     * Get recent activities (placeholder)
     */
    private function getRecentActivities(Faculty $faculty): array
    {
        // This is a placeholder - you can implement actual activity tracking later
        return [
            [
                'id' => 1,
                'type' => 'grade_submitted',
                'description' => 'Grades submitted for MATH101',
                'timestamp' => now()->subHours(2)->format('M d, Y H:i'),
            ],
            [
                'id' => 2,
                'type' => 'attendance_recorded',
                'description' => 'Attendance recorded for CS102',
                'timestamp' => now()->subHours(5)->format('M d, Y H:i'),
            ],
        ];
    }

    /**
     * Generate sample students for a class
     */
    private function generateSampleStudents(int $count): array
    {
        $sampleNames = [
            'John Doe', 'Jane Smith', 'Michael Johnson', 'Emily Davis', 'David Wilson',
            'Sarah Brown', 'James Jones', 'Jessica Garcia', 'Robert Miller', 'Ashley Martinez',
            'Christopher Anderson', 'Amanda Taylor', 'Matthew Thomas', 'Jennifer Jackson',
            'Joshua White', 'Stephanie Harris', 'Andrew Martin', 'Nicole Thompson',
            'Daniel Garcia', 'Elizabeth Rodriguez', 'Anthony Lewis', 'Heather Lee',
            'Mark Walker', 'Michelle Hall', 'Steven Allen', 'Kimberly Young',
            'Kenneth Hernandez', 'Lisa King', 'Paul Wright', 'Nancy Lopez',
            'Edward Hill', 'Karen Scott', 'Brian Green', 'Betty Adams',
            'Ronald Baker', 'Helen Gonzalez', 'Anthony Nelson', 'Sandra Carter',
            'Kevin Mitchell', 'Donna Perez', 'Jason Roberts', 'Carol Turner',
            'Jeffrey Phillips', 'Ruth Campbell', 'Ryan Parker', 'Sharon Evans',
            'Jacob Edwards', 'Michelle Collins', 'Gary Stewart', 'Laura Sanchez'
        ];

        $students = [];
        for ($i = 0; $i < $count; $i++) {
            $name = $sampleNames[$i % count($sampleNames)];
            $students[] = [
                'id' => $i + 1,
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@student.edu',
                'student_id' => '2024' . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
            ];
        }

        return $students;
    }

    /**
     * Generate a consistent color for a subject based on its code
     */
    private function generateColorForSubject(string $subjectCode): string
    {
        // Create a hash of the subject code
        $hash = crc32($subjectCode);

        // Define a set of pleasant colors
        $colors = [
            'blue-500', 'green-500', 'purple-500', 'amber-500', 'rose-500',
            'indigo-500', 'emerald-500', 'violet-500', 'orange-500', 'pink-500',
            'cyan-500', 'teal-500', 'fuchsia-500', 'yellow-500', 'red-500',
        ];

        // Use the hash to select a color
        $colorIndex = abs($hash) % count($colors);

        return $colors[$colorIndex];
    }
}
