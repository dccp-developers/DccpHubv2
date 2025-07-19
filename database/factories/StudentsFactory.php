<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Students;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Students>
 */
final class StudentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->numberBetween(2024001, 2024999),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'middle_name' => $this->faker->firstName(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'birth_date' => $this->faker->date('Y-m-d', '2005-12-31'),
            'age' => $this->faker->numberBetween(18, 25),
            'address' => $this->faker->address(),
            'contacts' => $this->faker->phoneNumber(),
            'course_id' => 1, // Default course ID
            'academic_year' => 2024,
            'email' => $this->faker->unique()->safeEmail(),
            'remarks' => null,
            'profile_url' => null,
            'student_contact_id' => null,
            'student_parent_info' => null,
            'student_education_id' => null,
            'student_personal_id' => null,
            'document_location_id' => null,
            'student_id' => null,
            'status' => 'active',
            'clearance_status' => 'pending',
        ];
    }
}
