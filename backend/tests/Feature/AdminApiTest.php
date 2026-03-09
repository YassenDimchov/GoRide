<?php

use App\Models\Driver;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    resetApiTestData();
});

test('non-admin cannot access admin users endpoint', function () {
    $user = User::create([
        'name' => 'Not Admin',
        'email' => 'not-admin@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/admin/users')
        ->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden');
});

test('admin can suspend a user', function () {
    $admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'admin',
    ]);

    $target = User::create([
        'name' => 'Target',
        'email' => 'target@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
        'suspended' => false,
    ]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/users/{$target->id}/suspended", [
        'suspended' => true,
    ])->assertOk()
        ->assertJsonPath('user.suspended', true)
        ->assertJsonPath('message', 'User suspended.');

    expect((bool) $target->fresh()->suspended)->toBeTrue();
});

test('admin cannot suspend their own account', function () {
    $admin = User::create([
        'name' => 'Self Admin',
        'email' => 'self-admin@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'admin',
    ]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/users/{$admin->id}/suspended", [
        'suspended' => true,
    ])->assertStatus(409)
        ->assertJsonPath('message', "Admin accounts can't be suspended.");
});

test('admin can promote user to driver and driver profile is created', function () {
    $admin = User::create([
        'name' => 'Role Admin',
        'email' => 'role-admin@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'admin',
    ]);

    $target = User::create([
        'name' => 'Promote Target',
        'email' => 'promote-target@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/users/{$target->id}/role", [
        'role' => 'driver',
    ])->assertOk()
        ->assertJsonPath('user.role', 'driver')
        ->assertJsonPath('message', 'User promoted to driver.');

    expect($target->fresh()->role)->toBe('driver');
    expect(Driver::where('user_id', $target->id)->exists())->toBeTrue();
});
