<?php

use App\Models\User;
use App\Models\Faculty;
use App\Models\Students;
use App\Models\ShsStudents;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleBasedLoginTest extends TestCase
{
    use DatabaseTransactions;

    protected Faculty $faculty;
    protected Students $student;
    protected ShsStudents $shsStudent;
    protected User $facultyUser;
    protected User $studentUser;
    protected User $shsStudentUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Faculty record (uses UUID)
        $this->faculty = Faculty::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Professor',
            'email' => 'faculty@test.com',
        ]);

        // Create Faculty User
        $this->facultyUser = User::factory()->create([
            'role' => 'faculty',
            'person_type' => Faculty::class,
            'person_id' => $this->faculty->faculty_id_number, // UUID
            'email' => $this->faculty->email,
            'name' => $this->faculty->first_name . ' ' . $this->faculty->last_name,
        ]);

        // Create College Student record (uses integer ID)
        $this->student = Students::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Student',
            'email' => 'student@test.com',
        ]);

        // Create College Student User
        $this->studentUser = User::factory()->create([
            'role' => 'student',
            'person_type' => Students::class,
            'person_id' => $this->student->id, // Integer
            'email' => $this->student->email,
            'name' => $this->student->first_name . ' ' . $this->student->last_name,
        ]);

        // Note: SHS Students functionality appears to be incomplete in the current system
        // Skipping SHS student creation for now

        // Create Admin User
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'person_type' => null,
            'person_id' => null,
            'email' => 'admin@test.com',
            'name' => 'System Administrator',
        ]);
    }

    /** @test */
    public function faculty_can_login_and_access_faculty_dashboard()
    {
        // Test login
        $response = $this->post('/login', [
            'email' => $this->facultyUser->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->facultyUser);

        // Test faculty can access faculty-specific routes
        $response = $this->actingAs($this->facultyUser)
            ->get('/faculty/attendance');

        $response->assertOk();
    }

    /** @test */
    public function college_student_can_login_and_access_student_dashboard()
    {
        // Test login
        $response = $this->post('/login', [
            'email' => $this->studentUser->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->studentUser);

        // Test student can access student-specific routes
        $response = $this->actingAs($this->studentUser)
            ->get('/student/attendance');

        $response->assertOk();

        // Verify the user has correct person relationship
        $this->assertInstanceOf(Students::class, $this->studentUser->student);
        $this->assertEquals($this->student->id, $this->studentUser->person_id);
        $this->assertEquals('student', $this->studentUser->role);
    }

    /** @test */
    public function shs_student_functionality_is_not_yet_implemented()
    {
        // This test documents that SHS student functionality is not yet fully implemented
        $this->markTestSkipped('SHS Students functionality is not yet fully implemented in the system');
    }

    /** @test */
    public function admin_can_login_and_access_admin_features()
    {
        // Test login
        $response = $this->post('/login', [
            'email' => $this->adminUser->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->adminUser);

        // Test admin can access dashboard (admin routes may not be fully implemented)
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard');

        $response->assertOk();

        // Verify admin role
        $this->assertEquals('admin', $this->adminUser->role);
        $this->assertNull($this->adminUser->person_id);
        $this->assertNull($this->adminUser->person_type);
    }

    /** @test */
    public function users_are_redirected_based_on_their_roles()
    {
        // Faculty should be redirected to faculty dashboard
        $response = $this->actingAs($this->facultyUser)
            ->get('/dashboard');
        $response->assertRedirect('/faculty/dashboard');

        // Students should access student dashboard directly
        $response = $this->actingAs($this->studentUser)
            ->get('/dashboard');
        $response->assertOk();

        // Admin should access admin dashboard directly
        $response = $this->actingAs($this->adminUser)
            ->get('/dashboard');
        $response->assertOk();
    }

    /** @test */
    public function faculty_cannot_access_student_only_routes()
    {
        $response = $this->actingAs($this->facultyUser)
            ->get('/student/attendance');

        $response->assertForbidden();
    }

    /** @test */
    public function students_cannot_access_faculty_only_routes()
    {
        $response = $this->actingAs($this->studentUser)
            ->get('/faculty/classes');

        $response->assertForbidden();
    }

    /** @test */
    public function person_relationships_work_correctly_with_different_id_types()
    {
        // Faculty with UUID
        $this->actingAs($this->facultyUser);
        $faculty = $this->facultyUser->faculty;
        $this->assertInstanceOf(Faculty::class, $faculty);
        $this->assertIsString($faculty->faculty_id_number); // UUID is string
        $this->assertEquals($this->faculty->faculty_id_number, $faculty->faculty_id_number);

        // College Student with integer ID
        $this->actingAs($this->studentUser);
        $student = $this->studentUser->student;
        $this->assertInstanceOf(Students::class, $student);
        $this->assertIsInt($student->id); // Student ID is integer
        $this->assertEquals($this->student->id, $student->id);
    }

    /** @test */
    public function user_helper_methods_work_correctly()
    {
        // Test faculty user helper methods
        $this->actingAs($this->facultyUser);
        $this->assertTrue($this->facultyUser->isFaculty());
        $this->assertFalse($this->facultyUser->isStudent());
        $this->assertFalse($this->facultyUser->isAdmin());

        // Test student user helper methods
        $this->actingAs($this->studentUser);
        $this->assertFalse($this->studentUser->isFaculty());
        $this->assertTrue($this->studentUser->isStudent());
        $this->assertFalse($this->studentUser->isAdmin());

        // Test admin user helper methods
        $this->actingAs($this->adminUser);
        $this->assertFalse($this->adminUser->isFaculty());
        $this->assertFalse($this->adminUser->isStudent());
        $this->assertTrue($this->adminUser->isAdmin());
    }
}
