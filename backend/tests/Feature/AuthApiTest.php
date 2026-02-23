<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    resetApiTestData();
});

test('register returns user and token', function () {
    $payload = [
        'name' => 'Test Rider',
        'email' => 'rider@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'phone' => '1234567890',
    ];

    $response = $this->postJson('/api/register', $payload);

    $response
        ->assertCreated()
        ->assertJsonPath('user.email', 'rider@example.com')
        ->assertJsonPath('user.role', 'user');

    expect($response->json('token'))->not->toBeEmpty();

    $this->assertDatabaseHas('users', [
        'email' => 'rider@example.com',
        'name' => 'Test Rider',
        'role' => 'user',
    ]);
});

test('login rejects suspended users', function () {
    User::create([
        'name' => 'Suspended User',
        'email' => 'suspended@example.com',
        'password' => Hash::make('secret123'),
        'role' => 'user',
        'suspended' => true,
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'suspended@example.com',
        'password' => 'secret123',
    ]);

    $response
        ->assertStatus(403)
        ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');
});
