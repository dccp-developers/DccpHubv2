<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Enums\AttendanceStatus;

class AttendanceStatusTest extends TestCase
{
    /** @test */
    public function it_has_correct_status_values(): void
    {
        $this->assertEquals('present', AttendanceStatus::PRESENT->value);
        $this->assertEquals('absent', AttendanceStatus::ABSENT->value);
        $this->assertEquals('late', AttendanceStatus::LATE->value);
        $this->assertEquals('excused', AttendanceStatus::EXCUSED->value);
        $this->assertEquals('partial', AttendanceStatus::PARTIAL->value);
    }

    /** @test */
    public function it_provides_correct_labels(): void
    {
        $this->assertEquals('Present', AttendanceStatus::PRESENT->label());
        $this->assertEquals('Absent', AttendanceStatus::ABSENT->label());
        $this->assertEquals('Late', AttendanceStatus::LATE->label());
        $this->assertEquals('Excused', AttendanceStatus::EXCUSED->label());
        $this->assertEquals('Partial', AttendanceStatus::PARTIAL->label());
    }

    /** @test */
    public function it_provides_correct_colors(): void
    {
        $this->assertEquals('success', AttendanceStatus::PRESENT->color());
        $this->assertEquals('destructive', AttendanceStatus::ABSENT->color());
        $this->assertEquals('warning', AttendanceStatus::LATE->color());
        $this->assertEquals('secondary', AttendanceStatus::EXCUSED->color());
        $this->assertEquals('info', AttendanceStatus::PARTIAL->color());
    }

    /** @test */
    public function it_provides_correct_icons(): void
    {
        $this->assertEquals('check-circle', AttendanceStatus::PRESENT->icon());
        $this->assertEquals('x-circle', AttendanceStatus::ABSENT->icon());
        $this->assertEquals('clock', AttendanceStatus::LATE->icon());
        $this->assertEquals('shield-check', AttendanceStatus::EXCUSED->icon());
        $this->assertEquals('minus-circle', AttendanceStatus::PARTIAL->icon());
    }

    /** @test */
    public function it_correctly_identifies_present_statuses(): void
    {
        $this->assertTrue(AttendanceStatus::PRESENT->isPresent());
        $this->assertTrue(AttendanceStatus::LATE->isPresent());
        $this->assertTrue(AttendanceStatus::PARTIAL->isPresent());
        
        $this->assertFalse(AttendanceStatus::ABSENT->isPresent());
        $this->assertFalse(AttendanceStatus::EXCUSED->isPresent());
    }

    /** @test */
    public function it_correctly_identifies_absent_statuses(): void
    {
        $this->assertTrue(AttendanceStatus::ABSENT->isAbsent());
        
        $this->assertFalse(AttendanceStatus::PRESENT->isAbsent());
        $this->assertFalse(AttendanceStatus::LATE->isAbsent());
        $this->assertFalse(AttendanceStatus::PARTIAL->isAbsent());
        $this->assertFalse(AttendanceStatus::EXCUSED->isAbsent());
    }

    /** @test */
    public function it_provides_all_status_options(): void
    {
        $options = AttendanceStatus::options();
        
        $this->assertIsArray($options);
        $this->assertCount(5, $options);
        
        foreach ($options as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertArrayHasKey('color', $option);
            $this->assertArrayHasKey('icon', $option);
        }
    }

    /** @test */
    public function it_provides_present_statuses_list(): void
    {
        $presentStatuses = AttendanceStatus::presentStatuses();
        
        $this->assertIsArray($presentStatuses);
        $this->assertCount(3, $presentStatuses);
        $this->assertContains(AttendanceStatus::PRESENT, $presentStatuses);
        $this->assertContains(AttendanceStatus::LATE, $presentStatuses);
        $this->assertContains(AttendanceStatus::PARTIAL, $presentStatuses);
    }

    /** @test */
    public function it_provides_absent_statuses_list(): void
    {
        $absentStatuses = AttendanceStatus::absentStatuses();
        
        $this->assertIsArray($absentStatuses);
        $this->assertCount(1, $absentStatuses);
        $this->assertContains(AttendanceStatus::ABSENT, $absentStatuses);
    }

    /** @test */
    public function it_can_be_created_from_string(): void
    {
        $this->assertEquals(AttendanceStatus::PRESENT, AttendanceStatus::from('present'));
        $this->assertEquals(AttendanceStatus::ABSENT, AttendanceStatus::from('absent'));
        $this->assertEquals(AttendanceStatus::LATE, AttendanceStatus::from('late'));
        $this->assertEquals(AttendanceStatus::EXCUSED, AttendanceStatus::from('excused'));
        $this->assertEquals(AttendanceStatus::PARTIAL, AttendanceStatus::from('partial'));
    }

    /** @test */
    public function it_throws_exception_for_invalid_status(): void
    {
        $this->expectException(\ValueError::class);
        AttendanceStatus::from('invalid_status');
    }

    /** @test */
    public function it_can_try_from_string_safely(): void
    {
        $this->assertEquals(AttendanceStatus::PRESENT, AttendanceStatus::tryFrom('present'));
        $this->assertNull(AttendanceStatus::tryFrom('invalid_status'));
    }

    /** @test */
    public function all_cases_are_covered(): void
    {
        $cases = AttendanceStatus::cases();
        
        $this->assertCount(5, $cases);
        $this->assertContains(AttendanceStatus::PRESENT, $cases);
        $this->assertContains(AttendanceStatus::ABSENT, $cases);
        $this->assertContains(AttendanceStatus::LATE, $cases);
        $this->assertContains(AttendanceStatus::EXCUSED, $cases);
        $this->assertContains(AttendanceStatus::PARTIAL, $cases);
    }
}
