<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Students;
use App\Models\Faculty;
use App\Services\SocialAuthService;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

class SocialAuthenticationTest extends TestCase
{
    protected Students $testStudent;
    protected Faculty $testFaculty;
    protected string $studentEmail;
    protected string $facultyEmail;

    protected function setUp(): void
    {
        parent::setUp();

        // Fix the person_id column type to support UUIDs
        \Illuminate\Support\Facades\Schema::table('accounts', function ($table) {
            $table->string('person_id', 255)->nullable()->change();
        });

        // Generate unique emails to avoid conflicts
        $uniqueId = uniqid('test_');
        $studentEmail = 'student_' . $uniqueId . '@test.com';
        $facultyEmail = 'faculty_' . $uniqueId . '@test.com';

        // Create test student and faculty records using factories
        $this->testStudent = Students::factory()->create([
            'id' => 2024000000 + (int) (microtime(true) * 1000) % 999999,
            'email' => $studentEmail,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'M',
        ]);

        $this->testFaculty = Faculty::factory()->create([
            'email' => $facultyEmail,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'middle_name' => 'A',
        ]);

        $this->studentEmail = $studentEmail;
        $this->facultyEmail = $facultyEmail;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_student_user_from_social_login_when_email_exists_in_student_records()
    {
        $socialAuthService = new SocialAuthService();
        
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($this->studentEmail);
        $socialiteUser->shouldReceive('getName')->andReturn('John M Doe');
        $socialiteUser->shouldReceive('getId')->andReturn('google123');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        $user = $socialAuthService->findOrCreateUser($socialiteUser, 'google');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->studentEmail, $user->email);
        $this->assertEquals('student', $user->role);
        $this->assertEquals($this->testStudent->id, $user->person_id);
        $this->assertEquals(Students::class, $user->person_type);
        $this->assertNotNull($user->email_verified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_faculty_user_from_social_login_when_email_exists_in_faculty_records()
    {
        $socialAuthService = new SocialAuthService();
        
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($this->facultyEmail);
        $socialiteUser->shouldReceive('getName')->andReturn('Jane A Smith');
        $socialiteUser->shouldReceive('getId')->andReturn('google456');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar2.jpg');

        $user = $socialAuthService->findOrCreateUser($socialiteUser, 'google');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->facultyEmail, $user->email);
        $this->assertEquals('faculty', $user->role);
        $this->assertEquals($this->testFaculty->id, $user->person_id);
        $this->assertEquals(Faculty::class, $user->person_type);
        $this->assertNotNull($user->email_verified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_updates_existing_user_from_social_login()
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => $this->studentEmail,
            'name' => 'Old Name',
            'avatar' => null,
            'email_verified_at' => null
        ]);

        $socialAuthService = new SocialAuthService();

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($this->studentEmail);
        $socialiteUser->shouldReceive('getName')->andReturn('Updated Name');
        $socialiteUser->shouldReceive('getId')->andReturn('google123');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/new-avatar.jpg');

        $user = $socialAuthService->findOrCreateUser($socialiteUser, 'google');

        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('https://example.com/new-avatar.jpg', $user->avatar);
        $this->assertNotNull($user->email_verified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_exists_in_records()
    {
        $socialAuthService = new SocialAuthService();

        $this->assertTrue($socialAuthService->validateEmailInRecords($this->studentEmail));
        $this->assertTrue($socialAuthService->validateEmailInRecords($this->facultyEmail));
        $this->assertFalse($socialAuthService->validateEmailInRecords('unknown@test.com'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_gets_person_data_by_email()
    {
        $socialAuthService = new SocialAuthService();

        $studentData = $socialAuthService->getPersonDataByEmail($this->studentEmail);
        $this->assertNotNull($studentData);
        $this->assertEquals('student', $studentData['role']);
        $this->assertEquals(Students::class, $studentData['type']);

        $facultyData = $socialAuthService->getPersonDataByEmail($this->facultyEmail);
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
