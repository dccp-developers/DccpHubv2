<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function (): void {
    // Create admin user
    $this->admin = User::factory()->create(['role' => 'admin']);

    // Create faculty user (has write permissions)
    $this->writer = User::factory()->create(['role' => 'faculty']);

    // Create regular student user (has read permissions)
    $this->reader = User::factory()->create(['role' => 'student']);
});

test('unauthenticated users cannot access api endpoints', function (): void {
    $response = $this->getJson('/api/user');
    $response->assertForbidden(); // API returns 403 for unauthenticated users
});

test('admin can list all users', function (): void {
    Sanctum::actingAs($this->admin, ['*']);

    $response = $this->getJson('/api/user');

    $response->assertOk();

    // Verify the response is a collection and contains our admin user
    $users = $response->json();
    expect($users)->toBeArray();
    expect(count($users))->toBeGreaterThan(0);

    // Check that our admin user is in the response
    $adminUser = collect($users)->firstWhere('id', $this->admin->id);
    expect($adminUser)->not->toBeNull();
    expect($adminUser['email'])->toBe($this->admin->email);
});

test('admin can create new user', function (): void {
    Sanctum::actingAs($this->admin, ['create']);

    // Create a student record first
    $student = \App\Models\Students::factory()->create([
        'email' => 'test-' . uniqid() . '@example.com',
    ]);

    $userData = [
        'id' => (string) $student->id,
        'name' => $student->first_name . ' ' . $student->last_name,
        'email' => $student->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
    ];

    $response = $this->postJson('/api/user', $userData);

    $response->assertCreated()
        ->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->where('name', $userData['name'])
                ->where('email', $userData['email'])
                ->where('role', $userData['role'])
                ->etc()
        );
});

test('admin can view specific user', function (): void {
    Sanctum::actingAs($this->admin, ['read']);

    $response = $this->getJson("/api/user/{$this->writer->id}");

    $response->assertOk()
        ->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->where('id', $this->writer->id)
                ->where('name', $this->writer->name)
                ->where('email', $this->writer->email)
                ->etc()
        );
});

test('admin can update user', function (): void {
    Sanctum::actingAs($this->admin, ['update']);

    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated-' . uniqid() . '@example.com',
        'is_active' => true,
    ];

    $response = $this->putJson("/api/user/{$this->writer->id}", $updateData);

    $response->assertOk()
        ->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->where('name', $updateData['name'])
                ->where('email', $updateData['email'])
                ->etc()
        );
});

test('admin can delete user', function (): void {
    Sanctum::actingAs($this->admin, ['delete']);

    $response = $this->deleteJson("/api/user/{$this->writer->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('accounts', ['id' => $this->writer->id]);
});

test('users can view their own profile', function (): void {
    Sanctum::actingAs($this->writer, ['read']);

    $response = $this->getJson("/api/user/{$this->writer->id}");

    $response->assertOk();
});

test('users cannot view other users profiles without permission', function (): void {
    Sanctum::actingAs($this->reader, ['read']);

    $response = $this->getJson("/api/user/{$this->writer->id}");

    $response->assertForbidden();
});

test('users cannot delete themselves', function (): void {
    Sanctum::actingAs($this->writer, ['delete']);

    $response = $this->deleteJson("/api/user/{$this->writer->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('accounts', ['id' => $this->writer->id]);
});

test('token abilities are properly checked', function (): void {
    // Create a student for the test
    $student1 = \App\Models\Students::factory()->create([
        'email' => 'token-test-1-' . uniqid() . '@example.com',
    ]);

    // Token with only read ability
    Sanctum::actingAs($this->admin, ['read']);
    $response = $this->postJson('/api/user', [
        'id' => (string) $student1->id,
        'name' => $student1->first_name . ' ' . $student1->last_name,
        'email' => $student1->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
    ]);
    $response->assertForbidden();

    // Create another student for the second test
    $student2 = \App\Models\Students::factory()->create([
        'email' => 'token-test-2-' . uniqid() . '@example.com',
    ]);

    // Token with create ability
    Sanctum::actingAs($this->admin, ['create']);
    $response = $this->postJson('/api/user', [
        'id' => (string) $student2->id,
        'name' => $student2->first_name . ' ' . $student2->last_name,
        'email' => $student2->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'student',
    ]);
    $response->assertCreated();
});
