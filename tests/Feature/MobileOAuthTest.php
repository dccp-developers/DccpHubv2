<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Students;
use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class MobileOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test student and faculty records directly
        Students::create([
            'id' => '2024001',
            'email' => 'student@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'M',
            'gender' => 'male',
            'birth_date' => '2000-01-01',
            'age' => 24,
            'address' => 'Test Address',
            'contacts' => '123456789',
            'course_id' => 1,
            'academic_year' => 2024,
            'status' => 'active',
            'clearance_status' => 'pending'
        ]);

        Faculty::create([
            'id' => 'FAC001',
            'email' => 'faculty@test.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'middle_name' => 'A',
            'department' => 'IT',
            'status' => 'active',
            'gender' => 'female',
            'age' => 35
        ]);
    }

    /** @test */
    public function it_handles_mobile_oauth_callback_for_student()
    {
        // Mock Google API response
        Http::fake([
            'https://www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id' => 'google123',
                'email' => 'student@test.com',
                'name' => 'John M Doe',
                'picture' => 'https://example.com/avatar.jpg'
            ])
        ]);

        $response = $this->postJson('/auth/google/callback/mobile', [
            'access_token' => 'fake_access_token',
            'provider' => 'google',
            'user_info' => [
                'id' => 'google123',
                'email' => 'student@test.com',
                'name' => 'John M Doe'
            ]
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'user' => [
                        'email' => 'student@test.com',
                        'name' => 'John M Doe'
                    ]
                ]);

        // Verify user was created with correct role and person linking
        $user = User::where('email', 'student@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('student', $user->role);
        $this->assertEquals('2024001', $user->person_id);
        $this->assertEquals(Students::class, $user->person_type);
        $this->assertEquals('google123', $user->google_id);
    }

    /** @test */
    public function it_handles_mobile_oauth_callback_for_faculty()
    {
        // Mock Google API response
        Http::fake([
            'https://www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id' => 'google456',
                'email' => 'faculty@test.com',
                'name' => 'Jane A Smith',
                'picture' => 'https://example.com/avatar2.jpg'
            ])
        ]);

        $response = $this->postJson('/auth/google/callback/mobile', [
            'access_token' => 'fake_access_token',
            'provider' => 'google',
            'user_info' => [
                'id' => 'google456',
                'email' => 'faculty@test.com',
                'name' => 'Jane A Smith'
            ]
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'user' => [
                        'email' => 'faculty@test.com',
                        'name' => 'Jane A Smith'
                    ]
                ]);

        // Verify user was created with correct role and person linking
        $user = User::where('email', 'faculty@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('faculty', $user->role);
        $this->assertEquals('FAC001', $user->person_id);
        $this->assertEquals(Faculty::class, $user->person_type);
        $this->assertEquals('google456', $user->google_id);
    }

    /** @test */
    public function it_rejects_mobile_oauth_callback_for_unregistered_email()
    {
        // Mock Google API response
        Http::fake([
            'https://www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id' => 'google789',
                'email' => 'unknown@test.com',
                'name' => 'Unknown User',
                'picture' => null
            ])
        ]);

        $response = $this->postJson('/auth/google/callback/mobile', [
            'access_token' => 'fake_access_token',
            'provider' => 'google',
            'user_info' => [
                'id' => 'google789',
                'email' => 'unknown@test.com',
                'name' => 'Unknown User'
            ]
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'error' => 'Validation Error'
                ])
                ->assertJsonFragment([
                    'message' => 'Your email address (unknown@test.com) is not found in our Student or Faculty records'
                ]);

        // Verify no user was created
        $user = User::where('email', 'unknown@test.com')->first();
        $this->assertNull($user);
    }

    /** @test */
    public function it_validates_required_fields_for_mobile_oauth_callback()
    {
        $response = $this->postJson('/auth/google/callback/mobile', [
            'provider' => 'google'
            // Missing access_token
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['access_token']);
    }

    /** @test */
    public function it_handles_google_api_failure_gracefully()
    {
        // Mock Google API failure
        Http::fake([
            'https://www.googleapis.com/oauth2/v2/userinfo*' => Http::response([], 401)
        ]);

        $response = $this->postJson('/auth/google/callback/mobile', [
            'access_token' => 'invalid_access_token',
            'provider' => 'google'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Failed to get user information from Google'
                ]);
    }

    /** @test */
    public function it_updates_existing_user_on_mobile_oauth_callback()
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'student@test.com',
            'name' => 'Old Name',
            'google_id' => null,
            'avatar' => null
        ]);

        // Mock Google API response
        Http::fake([
            'https://www.googleapis.com/oauth2/v2/userinfo*' => Http::response([
                'id' => 'google123',
                'email' => 'student@test.com',
                'name' => 'Updated Name',
                'picture' => 'https://example.com/new-avatar.jpg'
            ])
        ]);

        $response = $this->postJson('/auth/google/callback/mobile', [
            'access_token' => 'fake_access_token',
            'provider' => 'google'
        ]);

        $response->assertStatus(200);

        // Verify user was updated
        $user = User::find($existingUser->id);
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('google123', $user->google_id);
        $this->assertEquals('https://example.com/new-avatar.jpg', $user->avatar);
        $this->assertNotNull($user->email_verified_at);
    }
}
