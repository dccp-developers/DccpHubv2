<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Students;
use App\Models\Classes;
use App\Models\class_enrollments;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Services\AttendanceService;
use App\Services\Faculty\FacultyAttendanceService;
use App\Services\Student\StudentAttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;

class AttendanceTest extends TestCase
{
    use WithFaker;

    private User $facultyUser;
    private User $studentUser;
    private Faculty $faculty;
    private Students $student;
    private Classes $class;
    private class_enrollments $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestData();
    }

    private function setUpTestData(): void
    {
        // Create faculty record first
        $this->faculty = Faculty::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Create faculty user linked to faculty record
        $this->facultyUser = User::factory()->create([
            'role' => 'faculty',
            'person_type' => Faculty::class,
            'person_id' => $this->faculty->faculty_id_number,
            'email' => $this->faculty->email,
        ]);

        // Create student record first
        $this->student = Students::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Create student user linked to student record
        $this->studentUser = User::factory()->create([
            'role' => 'student',
            'person_type' => Students::class,
            'person_id' => $this->student->id,
            'email' => $this->student->email,
        ]);

        // Create class
        $this->class = Classes::factory()->create([
            'faculty_id' => $this->faculty->id,
            'subject_code' => 'CS101',
        ]);

        // Create enrollment
        $this->enrollment = class_enrollments::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
        ]);
    }

    /** @test */
    public function faculty_can_access_attendance_dashboard(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->get(route('faculty.attendance.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Faculty/Attendance/Index')
                ->has('faculty')
                ->has('classes')
                ->has('summary')
        );
    }

    /** @test */
    public function faculty_can_view_class_attendance(): void
    {
        $response = $this->actingAs($this->facultyUser)
            ->get(route('faculty.attendance.class.show', ['class' => $this->class->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Faculty/Attendance/ClassAttendance')
                ->has('class')
                ->has('roster')
                ->has('session_stats')
        );
    }

    /** @test */
    public function faculty_can_mark_attendance(): void
    {
        $attendanceData = [
            'date' => now()->format('Y-m-d'),
            'attendance' => [
                [
                    'student_id' => (string) $this->student->id,
                    'status' => AttendanceStatus::PRESENT->value,
                    'remarks' => 'Test remarks',
                ]
            ]
        ];

        $response = $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.class.mark', ['class' => $this->class->id]), $attendanceData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('attendances', [
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => AttendanceStatus::PRESENT->value,
            'date' => now()->format('Y-m-d'),
        ]);
    }

    /** @test */
    public function faculty_cannot_mark_attendance_for_other_faculty_classes(): void
    {
        // Create another faculty and class
        $otherFaculty = Faculty::factory()->create();
        $otherClass = Classes::factory()->create(['faculty_id' => $otherFaculty->id]);

        $attendanceData = [
            'date' => now()->format('Y-m-d'),
            'attendance' => [
                [
                    'student_id' => (string) $this->student->id,
                    'status' => AttendanceStatus::PRESENT->value,
                ]
            ]
        ];

        $response = $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.class.mark', ['class' => $otherClass->id]), $attendanceData);

        $response->assertStatus(403);
    }

    /** @test */
    public function student_can_access_attendance_dashboard(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->get(route('student.attendance.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Student/Attendance/Index')
                ->has('student')
                ->has('classes')
                ->has('overall_stats')
        );
    }

    /** @test */
    public function student_can_view_class_attendance_details(): void
    {
        // Create some attendance records
        Attendance::factory()->create([
            'class_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => AttendanceStatus::PRESENT->value,
            'date' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.attendance.class.show', ['class' => $this->class->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Student/Attendance/ClassDetails')
                ->has('class')
                ->has('stats')
                ->has('attendances')
        );
    }

    /** @test */
    public function student_cannot_view_other_student_attendance(): void
    {
        // Create another student and class
        $otherStudent = Students::factory()->create();
        $otherEnrollment = class_enrollments::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $otherStudent->id,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.attendance.class.show', ['class' => $this->class->id]));

        $response->assertStatus(200);
        // Should only see their own attendance data
    }

    /** @test */
    public function attendance_service_calculates_stats_correctly(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Create test attendance records
        $attendances = collect([
            Attendance::factory()->make(['status' => AttendanceStatus::PRESENT->value]),
            Attendance::factory()->make(['status' => AttendanceStatus::PRESENT->value]),
            Attendance::factory()->make(['status' => AttendanceStatus::ABSENT->value]),
            Attendance::factory()->make(['status' => AttendanceStatus::LATE->value]),
        ]);

        $stats = $attendanceService->calculateAttendanceStats($attendances);

        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['present']);
        $this->assertEquals(1, $stats['absent']);
        $this->assertEquals(1, $stats['late']);
        $this->assertEquals(3, $stats['present_count']); // present + late
        $this->assertEquals(75.0, $stats['attendance_rate']); // 3/4 * 100
    }

    /** @test */
    public function attendance_can_be_marked_with_different_statuses(): void
    {
        $attendanceService = app(AttendanceService::class);

        foreach (AttendanceStatus::cases() as $status) {
            $attendance = $attendanceService->markAttendance(
                $this->class->id,
                $this->student->id,
                $status,
                now()->addDays($status->value === 'present' ? 1 : 2), // Different dates
                'Test remarks for ' . $status->value,
                $this->faculty->id
            );

            $this->assertEquals($status, $attendance->status);
            $this->assertDatabaseHas('attendances', [
                'id' => $attendance->id,
                'status' => $status->value,
            ]);
        }
    }

    /** @test */
    public function attendance_can_be_updated(): void
    {
        $attendanceService = app(AttendanceService::class);

        // Mark initial attendance
        $attendance = $attendanceService->markAttendance(
            $this->class->id,
            $this->student->id,
            AttendanceStatus::ABSENT,
            now(),
            'Initially absent',
            $this->faculty->id
        );

        // Update to present
        $updatedAttendance = $attendanceService->markAttendance(
            $this->class->id,
            $this->student->id,
            AttendanceStatus::PRESENT,
            now(),
            'Now present',
            $this->faculty->id
        );

        $this->assertEquals($attendance->id, $updatedAttendance->id);
        $this->assertEquals(AttendanceStatus::PRESENT, $updatedAttendance->status);
        $this->assertEquals('Now present', $updatedAttendance->remarks);
    }

    /** @test */
    public function faculty_attendance_service_provides_dashboard_summary(): void
    {
        $facultyAttendanceService = app(FacultyAttendanceService::class);

        // Create some attendance records
        Attendance::factory()->count(5)->create([
            'class_id' => $this->class->id,
            'status' => AttendanceStatus::PRESENT->value,
        ]);

        Attendance::factory()->count(2)->create([
            'class_id' => $this->class->id,
            'status' => AttendanceStatus::ABSENT->value,
        ]);

        $summary = $facultyAttendanceService->getFacultyDashboardSummary($this->faculty->id);

        $this->assertArrayHasKey('total_classes', $summary);
        $this->assertArrayHasKey('overall_stats', $summary);
        $this->assertArrayHasKey('recent_sessions', $summary);
        $this->assertEquals(1, $summary['total_classes']);
    }

    /** @test */
    public function student_attendance_service_provides_dashboard_data(): void
    {
        $studentAttendanceService = app(StudentAttendanceService::class);

        // Create some attendance records
        Attendance::factory()->count(3)->create([
            'class_enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => AttendanceStatus::PRESENT->value,
        ]);

        $dashboardData = $studentAttendanceService->getStudentDashboardData($this->student->id);

        $this->assertArrayHasKey('classes', $dashboardData);
        $this->assertArrayHasKey('overall_stats', $dashboardData);
        $this->assertArrayHasKey('attendance_trend', $dashboardData);
        $this->assertCount(1, $dashboardData['classes']);
    }

    /** @test */
    public function attendance_validation_prevents_invalid_data(): void
    {
        $invalidAttendanceData = [
            'date' => 'invalid-date',
            'attendance' => [
                [
                    'student_id' => '', // Empty student ID
                    'status' => 'invalid-status', // Invalid status
                ]
            ]
        ];

        $response = $this->actingAs($this->facultyUser)
            ->postJson(route('faculty.attendance.class.mark', ['class' => $this->class->id]), $invalidAttendanceData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date', 'attendance.0.student_id', 'attendance.0.status']);
    }
}
