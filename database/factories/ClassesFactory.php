<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Classes;
use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classes>
 */
final class ClassesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => null,
            'subject_code' => $this->faker->regexify('[A-Z]{3}[0-9]{3}'),
            'faculty_id' => Faculty::factory(),
            'academic_year' => $this->faker->randomElement(['2023-2024', '2024-2025']),
            'semester' => $this->faker->randomElement(['1st', '2nd']),
            'schedule_id' => null,
            'school_year' => '2024-2025',
            'course_codes' => [1, 2],
            'section' => $this->faker->randomElement(['A', 'B', 'C']),
            'room_id' => null,
            'classification' => $this->faker->randomElement(['Regular', 'Special']),
            'maximum_slots' => $this->faker->numberBetween(20, 40),
            'shs_track_id' => null,
            'shs_strand_id' => null,
            'grade_level' => null,
        ];
    }
}
