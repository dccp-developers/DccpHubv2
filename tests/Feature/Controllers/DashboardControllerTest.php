<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Students;
use Inertia\Testing\AssertableInertia;
use App\Http\Controllers\DashboardController;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;

covers(DashboardController::class);

beforeEach(function (): void {
    // Create a student record first
    $student = Students::factory()->create();

    // Create a user linked to the student
    $this->user = User::factory()->create([
        'role' => 'student',
        'person_id' => $student->id,
        'person_type' => Students::class,
    ]);
});

test('guests cannot access dashboard', function (): void {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can access dashboard', function (): void {
    $response = $this->actingAs($this->user)
        ->get(route('dashboard'));

    // Debug the response if it's not OK
    if ($response->status() !== 200) {
        dump('Response status: ' . $response->status());
        dump('Response content: ' . $response->getContent());
    }

    $response->assertInertia(
        fn (Assert $page): AssertableJson => $page
            ->component('Dashboard')
            ->has('auth.user')
            ->where('auth.user.id', $this->user->id)
    );
});

test('dashboard returns correct status code', function (): void {
    $response = $this->actingAs($this->user)
        ->get(route('dashboard'));

    $response->assertStatus(200);
});

test('dashboard uses correct inertia component', function (): void {
    $response = $this->actingAs($this->user)
        ->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page): AssertableInertia => $page
            ->component('Dashboard')
    );
});

test('dashboard has required shared data', function (): void {
    $response = $this->actingAs($this->user)
        ->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page): AssertableJson => $page
            ->has('auth')
            ->has('auth.user')
            ->has('auth.user.name')
            ->has('auth.user.email')
    );
});
