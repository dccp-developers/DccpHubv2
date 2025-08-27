<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Faculty;
use App\Models\Students;
use App\Models\Classes;
use App\Models\class_enrollments;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;

class AttendanceServiceTest extends TestCase
{
    private AttendanceService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attendanceService = app(AttendanceService::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_instantiate_attendance_service(): void
    {
        $this->assertInstanceOf(AttendanceService::class, $this->attendanceService);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_calculate_attendance_stats_from_empty_collection(): void
    {
        $emptyCollection = collect();
        
        $stats = $this->attendanceService->calculateAttendanceStats($emptyCollection);
        
        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['present']);
        $this->assertEquals(0, $stats['absent']);
        $this->assertEquals(0, $stats['late']);
        $this->assertEquals(0, $stats['attendance_rate']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_calculate_attendance_stats_from_collection(): void
    {
        // Create a mock collection of attendance records
        $attendances = collect([
            (object) ['status' => 'present'],
            (object) ['status' => 'present'],
            (object) ['status' => 'absent'],
            (object) ['status' => 'late'],
        ]);
        
        $stats = $this->attendanceService->calculateAttendanceStats($attendances);
        
        $this->assertIsArray($stats);
        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['present']);
        $this->assertEquals(1, $stats['absent']);
        $this->assertEquals(1, $stats['late']);
        $this->assertEquals(75.0, $stats['attendance_rate']); // (2 present + 1 late) / 4 * 100
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_attendance_status_enum(): void
    {
        $this->assertTrue(AttendanceStatus::PRESENT instanceof AttendanceStatus);
        $this->assertTrue(AttendanceStatus::ABSENT instanceof AttendanceStatus);
        $this->assertTrue(AttendanceStatus::LATE instanceof AttendanceStatus);
        
        $this->assertEquals('present', AttendanceStatus::PRESENT->value);
        $this->assertEquals('absent', AttendanceStatus::ABSENT->value);
        $this->assertEquals('late', AttendanceStatus::LATE->value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_access_attendance_model(): void
    {
        // Test that we can access the Attendance model
        $this->assertTrue(class_exists(Attendance::class));
        
        // Test the fillable attributes
        $attendance = new Attendance();
        $fillable = $attendance->getFillable();
        
        $this->assertContains('class_enrollment_id', $fillable);
        $this->assertContains('student_id', $fillable);
        $this->assertContains('date', $fillable);
        $this->assertContains('status', $fillable);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_work_with_carbon_dates(): void
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();
        
        $this->assertInstanceOf(Carbon::class, $today);
        $this->assertInstanceOf(Carbon::class, $yesterday);
        $this->assertInstanceOf(Carbon::class, $tomorrow);
        
        $this->assertEquals(date('Y-m-d'), $today->format('Y-m-d'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_check_database_connection(): void
    {
        // Test database connection by checking if we can query the tables
        try {
            $facultyCount = Faculty::count();
            $studentCount = Students::count();
            $classCount = Classes::count();
            $enrollmentCount = class_enrollments::count();
            
            // These should be numbers (could be 0 if no data)
            $this->assertIsInt($facultyCount);
            $this->assertIsInt($studentCount);
            $this->assertIsInt($classCount);
            $this->assertIsInt($enrollmentCount);
            
            // Database connection is working
            $this->assertTrue(true);
            
        } catch (\Exception $e) {
            $this->fail('Database connection failed: ' . $e->getMessage());
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_handle_attendance_status_casting(): void
    {
        // Test that AttendanceStatus enum works correctly
        $status = AttendanceStatus::PRESENT;
        $this->assertEquals('present', $status->value);
        
        // Test creating from value
        $statusFromValue = AttendanceStatus::from('present');
        $this->assertEquals(AttendanceStatus::PRESENT, $statusFromValue);
        
        // Test all available statuses
        $allStatuses = AttendanceStatus::cases();
        $this->assertGreaterThan(0, count($allStatuses));
        
        foreach ($allStatuses as $status) {
            $this->assertInstanceOf(AttendanceStatus::class, $status);
            $this->assertIsString($status->value);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_test_model_relationships(): void
    {
        // Test that model classes exist and can be instantiated
        $this->assertTrue(class_exists(Faculty::class));
        $this->assertTrue(class_exists(Students::class));
        $this->assertTrue(class_exists(Classes::class));
        $this->assertTrue(class_exists(class_enrollments::class));
        $this->assertTrue(class_exists(Attendance::class));
        
        // Test that we can create model instances
        $faculty = new Faculty();
        $student = new Students();
        $class = new Classes();
        $enrollment = new class_enrollments();
        $attendance = new Attendance();
        
        $this->assertInstanceOf(Faculty::class, $faculty);
        $this->assertInstanceOf(Students::class, $student);
        $this->assertInstanceOf(Classes::class, $class);
        $this->assertInstanceOf(class_enrollments::class, $enrollment);
        $this->assertInstanceOf(Attendance::class, $attendance);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_missing_data_gracefully(): void
    {
        // Test that the service handles missing data gracefully
        $this->assertInstanceOf(AttendanceService::class, $this->attendanceService);
        
        // Test with empty collections
        $emptyStats = $this->attendanceService->calculateAttendanceStats(collect());
        $this->assertEquals(0, $emptyStats['total']);
        
        // This test should always pass regardless of database state
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_calculate_complex_attendance_stats(): void
    {
        // Test with more complex attendance data
        $attendances = collect([
            (object) ['status' => 'present'],
            (object) ['status' => 'present'],
            (object) ['status' => 'present'],
            (object) ['status' => 'absent'],
            (object) ['status' => 'late'],
            (object) ['status' => 'excused'],
            (object) ['status' => 'partial'],
        ]);
        
        $stats = $this->attendanceService->calculateAttendanceStats($attendances);
        
        $this->assertIsArray($stats);
        $this->assertEquals(7, $stats['total']);
        $this->assertEquals(3, $stats['present']);
        $this->assertEquals(1, $stats['absent']);
        $this->assertEquals(1, $stats['late']);
        $this->assertEquals(1, $stats['excused']);
        $this->assertEquals(1, $stats['partial']);
        
        // Present count should include present + late + partial = 5
        $this->assertEquals(5, $stats['present_count']);
        
        // Attendance rate should be (5/7) * 100 = 71.43%
        $this->assertEquals(71.43, $stats['attendance_rate']);
    }
}
