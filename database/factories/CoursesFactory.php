<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Courses;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Courses>
 */
final class CoursesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'department' => $this->faker->randomElement(['IT', 'Engineering', 'Business', 'Education']),
            'remarks' => $this->faker->optional()->sentence(),
            'lec_per_unit' => $this->faker->numberBetween(1, 3),
            'lab_per_unit' => $this->faker->numberBetween(0, 3),
        ];
    }
}
