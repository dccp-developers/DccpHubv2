<?php

namespace Database\Factories;

use App\Models\ShsStudents;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShsStudents>
 */
class ShsStudentsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ShsStudents::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_lrn' => fake()->unique()->numerify('############'), // 12-digit LRN
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'suffix' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'birth_date' => fake()->date(),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Divorced', 'Widowed']),
            'nationality' => 'Filipino',
            'religion' => fake()->randomElement(['Catholic', 'Protestant', 'Islam', 'Buddhist', 'Other']),
            'guardian_name' => fake()->name(),
            'guardian_phone' => fake()->phoneNumber(),
            'guardian_email' => fake()->safeEmail(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'emergency_contact_relationship' => fake()->randomElement(['Parent', 'Sibling', 'Relative', 'Friend']),
            'strand' => fake()->randomElement(['STEM', 'ABM', 'HUMSS', 'GAS', 'TVL-ICT', 'TVL-HE']),
            'grade_level' => fake()->randomElement(['11', '12']),
            'section' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'school_year' => fake()->randomElement(['2023-2024', '2024-2025']),
            'enrollment_status' => fake()->randomElement(['enrolled', 'dropped', 'graduated', 'transferred']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the student is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'enrollment_status' => 'dropped',
        ]);
    }

    /**
     * Indicate that the student is in Grade 11.
     */
    public function grade11(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade_level' => '11',
        ]);
    }

    /**
     * Indicate that the student is in Grade 12.
     */
    public function grade12(): static
    {
        return $this->state(fn (array $attributes) => [
            'grade_level' => '12',
        ]);
    }

    /**
     * Indicate that the student is in STEM strand.
     */
    public function stem(): static
    {
        return $this->state(fn (array $attributes) => [
            'strand' => 'STEM',
        ]);
    }

    /**
     * Indicate that the student is in ABM strand.
     */
    public function abm(): static
    {
        return $this->state(fn (array $attributes) => [
            'strand' => 'ABM',
        ]);
    }

    /**
     * Indicate that the student is in HUMSS strand.
     */
    public function humss(): static
    {
        return $this->state(fn (array $attributes) => [
            'strand' => 'HUMSS',
        ]);
    }
}
