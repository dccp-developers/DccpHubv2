<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Faculty>
 */
final class FacultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'faculty_id_number' => $this->faker->unique()->numberBetween(1000001, 9999999),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'middle_name' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'phone_number' => $this->faker->phoneNumber(),
            'department' => $this->faker->randomElement(['IT', 'Engineering', 'Business', 'Education']),
            'office_hours' => '8:00 AM - 5:00 PM',
            'birth_date' => $this->faker->date('Y-m-d', '1980-12-31'),
            'address_line1' => $this->faker->address(),
            'biography' => $this->faker->paragraph(),
            'education' => $this->faker->sentence(),
            'courses_taught' => $this->faker->words(3, true),
            'photo_url' => null,
            'status' => 'active',
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'age' => $this->faker->numberBetween(25, 65),
        ];
    }
}
