<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Students;
use App\Models\Faculty;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

class SocialAuthenticationTest extends TestCase
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
    public function it_creates_student_user_from_social_login_when_email_exists_in_student_records()
    {
        $socialAuthService = new SocialAuthService();
        
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('student@test.com');
        $socialiteUser->shouldReceive('getName')->andReturn('John M Doe');
        $socialiteUser->shouldReceive('getId')->andReturn('google123');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $user = $socialAuthService->findOrCreateUser($socialiteUser, 'google');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('student@test.com', $user->email);
        $this->assertEquals('student', $user->role);
        $this->assertEquals('2024001', $user->person_id);
        $this->assertEquals(Students::class, $user->person_type);
        $this->assertEquals('google123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function it_creates_faculty_user_from_social_login_when_email_exists_in_faculty_records()
    {
        $socialAuthService = new SocialAuthService();
        
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('faculty@test.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Jane A Smith');
        $socialiteUser->shouldReceive('getId')->andReturn('google456');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar2.jpg');

        $user = $socialAuthService->findOrCreateUser($socialiteUser, 'google');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('faculty@test.com', $user->email);
        $this->assertEquals('faculty', $user->role);
        $this->assertEquals('FAC001', $user->person_id);
        $this->assertEquals(Faculty::class, $user->person_type);
        $this->assertEquals('google456', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function it_throws_exception_when_email_not_found_in_student_or_faculty_records()
    {
        $socialAuthService = new SocialAuthService();
        
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('unknown@test.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Unknown User');
        $socialiteUser->shouldReceive('getId')->andReturn('google789');
        $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Your email address (unknown@test.com) is not found in our Student or Faculty records');

        $socialAuthService->findOrCreateUser($socialiteUser, 'google');
    }

    /** @test */
    public function it_updates_existing_user_from_social_login()
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'student@test.com',
            'name' => 'Old Name',
            'google_id' => null,
            'avatar' => null,
            'email_verified_at' => null
        ]);

        $socialAuthService = new SocialAuthService();
        
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('student@test.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Updated Name');
        $socialiteUser->shouldReceive('getId')->andReturn('google123');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/new-avatar.jpg');

        $user = $socialAuthService->findOrCreateUser($socialiteUser, 'google');

        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('google123', $user->google_id);
        $this->assertEquals('https://example.com/new-avatar.jpg', $user->avatar);
        $this->assertNotNull($user->email_verified_at);
    }

    /** @test */
    public function it_validates_email_exists_in_records()
    {
        $socialAuthService = new SocialAuthService();

        $this->assertTrue($socialAuthService->validateEmailInRecords('student@test.com'));
        $this->assertTrue($socialAuthService->validateEmailInRecords('faculty@test.com'));
        $this->assertFalse($socialAuthService->validateEmailInRecords('unknown@test.com'));
    }

    /** @test */
    public function it_gets_person_data_by_email()
    {
        $socialAuthService = new SocialAuthService();

        $studentData = $socialAuthService->getPersonDataByEmail('student@test.com');
        $this->assertNotNull($studentData);
        $this->assertEquals('student', $studentData['role']);
        $this->assertEquals(Students::class, $studentData['type']);

        $facultyData = $socialAuthService->getPersonDataByEmail('faculty@test.com');
        $this->assertNotNull($facultyData);
        $this->assertEquals('faculty', $facultyData['role']);
        $this->assertEquals(Faculty::class, $facultyData['type']);

        $unknownData = $socialAuthService->getPersonDataByEmail('unknown@test.com');
        $this->assertNull($unknownData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
