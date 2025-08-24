<?php

declare(strict_types=1);

use App\Models\User;
use App\Policies\UserPolicy;

beforeEach(function (): void {
    $this->policy = new UserPolicy();

    // Create admin user
    $this->admin = User::factory()->create(['role' => 'admin']);

    // Create faculty user
    $this->faculty = User::factory()->create(['role' => 'faculty']);

    // Create regular student user
    $this->student = User::factory()->create(['role' => 'student']);

    // Create another user
    $this->otherUser = User::factory()->create(['role' => 'student']);
});

test('admin can view any users', function (): void {
    expect($this->policy->viewAny($this->admin))->toBeTrue();
});

test('admin can view specific user', function (): void {
    expect($this->policy->view($this->admin, $this->student))->toBeTrue();
});

test('users can view their own profile', function (): void {
    expect($this->policy->view($this->student, $this->student))->toBeTrue();
});

test('users cannot view other users profiles', function (): void {
    expect($this->policy->view($this->student, $this->otherUser))->toBeFalse();
});

test('admin can create users', function (): void {
    expect($this->policy->create($this->admin))->toBeTrue();
});

test('faculty can create users', function (): void {
    expect($this->policy->create($this->faculty))->toBeTrue();
});

test('users can update their own profile', function (): void {
    expect($this->policy->update($this->student, $this->student))->toBeTrue();
});

test('admin can update other users', function (): void {
    expect($this->policy->update($this->admin, $this->student))->toBeTrue();
});

test('users cannot delete themselves', function (): void {
    expect($this->policy->delete($this->student, $this->student))->toBeFalse();
});

test('only admin can delete users', function (): void {
    expect($this->policy->delete($this->admin, $this->student))->toBeTrue();
    expect($this->policy->delete($this->student, $this->admin))->toBeFalse();
});

test('only admin can restore users', function (): void {
    expect($this->policy->restore($this->admin, $this->student))->toBeTrue();
    expect($this->policy->restore($this->student, $this->admin))->toBeFalse();
});

test('only admin can force delete users', function (): void {
    expect($this->policy->forceDelete($this->admin, $this->student))->toBeTrue();
    expect($this->policy->forceDelete($this->student, $this->admin))->toBeFalse();
});
