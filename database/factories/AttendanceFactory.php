<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\class_enrollments;
use App\Models\Faculty;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_enrollment_id' => class_enrollments::factory(),
            'student_id' => function (array $attributes) {
                return class_enrollments::find($attributes['class_enrollment_id'])->student_id;
            },
            'class_id' => function (array $attributes) {
                return class_enrollments::find($attributes['class_enrollment_id'])->class_id;
            },
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status' => $this->faker->randomElement(AttendanceStatus::cases())->value,
            'remarks' => $this->faker->optional(0.3)->sentence(),
            'marked_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'marked_by' => Faculty::factory(),
            'ip_address' => $this->faker->ipv4(),
            'location_data' => $this->faker->optional(0.2)->passthrough([
                'latitude' => $this->faker->latitude(),
                'longitude' => $this->faker->longitude(),
                'accuracy' => $this->faker->numberBetween(1, 100),
            ]),
        ];
    }

    /**
     * Create attendance with present status
     */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::PRESENT->value,
            'remarks' => null,
        ]);
    }

    /**
     * Create attendance with absent status
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::ABSENT->value,
            'remarks' => $this->faker->optional(0.5)->sentence(),
        ]);
    }

    /**
     * Create attendance with late status
     */
    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::LATE->value,
            'remarks' => $this->faker->optional(0.7)->randomElement([
                'Arrived 10 minutes late',
                'Traffic delay',
                'Transportation issue',
                'Late due to previous class',
            ]),
        ]);
    }

    /**
     * Create attendance with excused status
     */
    public function excused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::EXCUSED->value,
            'remarks' => $this->faker->randomElement([
                'Medical appointment',
                'Family emergency',
                'Official school activity',
                'Approved leave',
                'Sick leave',
            ]),
        ]);
    }

    /**
     * Create attendance with partial status
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::PARTIAL->value,
            'remarks' => $this->faker->randomElement([
                'Left early due to emergency',
                'Attended first half only',
                'Arrived late, left early',
                'Partial attendance due to schedule conflict',
            ]),
        ]);
    }

    /**
     * Create attendance for a specific student
     */
    public function forStudent(string $studentId): static
    {
        return $this->state(fn (array $attributes) => [
            'student_id' => $studentId,
        ]);
    }

    /**
     * Create attendance for a specific class
     */
    public function forClass(int $classId): static
    {
        return $this->state(fn (array $attributes) => [
            'class_id' => $classId,
        ]);
    }

    /**
     * Create attendance for a specific date
     */
    public function forDate(Carbon|string $date): static
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        
        return $this->state(fn (array $attributes) => [
            'date' => $dateString,
            'marked_at' => Carbon::parse($dateString)->addHours($this->faker->numberBetween(8, 17)),
        ]);
    }

    /**
     * Create attendance marked by a specific faculty
     */
    public function markedBy(string $facultyId): static
    {
        return $this->state(fn (array $attributes) => [
            'marked_by' => $facultyId,
        ]);
    }

    /**
     * Create attendance with location data
     */
    public function withLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_data' => [
                'latitude' => $this->faker->latitude(),
                'longitude' => $this->faker->longitude(),
                'accuracy' => $this->faker->numberBetween(1, 100),
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Create attendance without location data
     */
    public function withoutLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_data' => null,
        ]);
    }

    /**
     * Create attendance with remarks
     */
    public function withRemarks(?string $remarks = null): static
    {
        return $this->state(fn (array $attributes) => [
            'remarks' => $remarks ?? $this->faker->sentence(),
        ]);
    }

    /**
     * Create attendance without remarks
     */
    public function withoutRemarks(): static
    {
        return $this->state(fn (array $attributes) => [
            'remarks' => null,
        ]);
    }

    /**
     * Create attendance for recent dates
     */
    public function recent(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween("-{$days} days", 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Create attendance for a specific week
     */
    public function forWeek(Carbon $weekStart): static
    {
        $weekEnd = $weekStart->copy()->endOfWeek();
        
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween($weekStart, $weekEnd)->format('Y-m-d'),
        ]);
    }

    /**
     * Create attendance for a specific month
     */
    public function forMonth(Carbon $month): static
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        
        return $this->state(fn (array $attributes) => [
            'date' => $this->faker->dateTimeBetween($monthStart, $monthEnd)->format('Y-m-d'),
        ]);
    }

    /**
     * Create attendance with good attendance pattern (mostly present)
     */
    public function goodAttendance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement([
                AttendanceStatus::PRESENT->value,
                AttendanceStatus::PRESENT->value,
                AttendanceStatus::PRESENT->value,
                AttendanceStatus::PRESENT->value,
                AttendanceStatus::LATE->value,
            ]),
        ]);
    }

    /**
     * Create attendance with poor attendance pattern (mostly absent)
     */
    public function poorAttendance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement([
                AttendanceStatus::ABSENT->value,
                AttendanceStatus::ABSENT->value,
                AttendanceStatus::ABSENT->value,
                AttendanceStatus::LATE->value,
                AttendanceStatus::PRESENT->value,
            ]),
        ]);
    }
}
