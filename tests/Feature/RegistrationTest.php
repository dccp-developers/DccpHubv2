<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class RegistrationTest extends TestCase
{
    // use RefreshDatabase;

    /** @test */
    public function it_checks_email_existence(): void
    {
        $response = $this->postJson('/api/check-email', [
            'email' => 'test@example.com',
            'userType' => 'student', // or 'instructor'
        ]);

        $response->assertStatus(200); // Check for a successful response
    }

    /** @test */
    public function it_checks_id_existence(): void
    {
        $response = $this->postJson('/api/check-id', [
            'id' => '12345',
            'userType' => 'student', // or 'instructor'
        ]);

        $response->assertStatus(200); // Check for a successful response
    }
}
