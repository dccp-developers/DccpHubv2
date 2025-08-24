<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\class_enrollments;
use App\Models\Classes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<class_enrollments>
 */
final class class_enrollmentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => Classes::factory(),
            'student_id' => fake()->unique()->numberBetween(20240001, 20249999),
            'completion_date' => null,
            'status' => true,
            'remarks' => null,
            'prelim_grade' => null,
            'midterm_grade' => null,
            'finals_grade' => null,
            'total_average' => null,
            'is_grades_finalized' => false,
            'is_grades_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
            'verification_notes' => null,
        ];
    }
}
