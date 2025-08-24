<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

test('changelog page loads successfully for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/changelog');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('Changelog/Index')
            ->has('releases')
    );
});

test('changelog displays releases data structure correctly', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/changelog');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('Changelog/Index')
            ->has('releases')
    );
});

test('changelog handles missing release directory gracefully', function () {
    $user = User::factory()->create();

    // Temporarily rename the directory to simulate missing releases
    $originalPath = base_path('release_descriptions');
    $tempPath = base_path('release_descriptions_temp');

    if (File::exists($originalPath)) {
        File::move($originalPath, $tempPath);
    }

    try {
        $response = $this->actingAs($user)->get('/changelog');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Changelog/Index')
                ->has('releases')
                ->where('releases', [])
        );
    } finally {
        // Restore the directory
        if (File::exists($tempPath)) {
            File::move($tempPath, $originalPath);
        }
    }
});

test('unauthenticated users are redirected to login', function () {
    $response = $this->get('/changelog');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});
