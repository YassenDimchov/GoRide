<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    resetApiTestData();
});

test('me returns authenticated user', function () {
    $user = User::create([
        'name' => 'Session User',
        'email' => 'session-user@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email', 'session-user@example.com');
});

test('logout deletes current token and token cannot be reused', function () {
    $user = User::create([
        'name' => 'Logout User',
        'email' => 'logout-user@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $access = $user->createToken('api');
    $token = $access->plainTextToken;
    $tokenId = $access->accessToken->id;

    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out');

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    app('auth')->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/me')
        ->assertStatus(401);
});

test('logout-all keeps current token and deletes other sessions', function () {
    $user = User::create([
        'name' => 'Logout All User',
        'email' => 'logout-all-user@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $old = $user->createToken('old-device');
    $current = $user->createToken('current-device');

    $this->assertDatabaseCount('personal_access_tokens', 2);

    $this->withToken($current->plainTextToken)
        ->postJson('/api/logout-all')
        ->assertOk()
        ->assertJsonPath('message', 'Logged out from all other devices.');

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $old->accessToken->id]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $current->accessToken->id]);
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

test('sessions endpoint returns current session marker', function () {
    $user = User::create([
        'name' => 'Sessions User',
        'email' => 'sessions-user@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $user->createToken('device-1');
    $current = $user->createToken('device-2');

    $response = $this->withToken($current->plainTextToken)
        ->getJson('/api/sessions')
        ->assertOk();

    expect($response->json('sessions'))->toBeArray();
    expect(collect($response->json('sessions'))->contains(fn ($s) => ($s['is_current'] ?? false) === true))->toBeTrue();
});

test('change-password updates password and old password no longer works', function () {
    $user = User::create([
        'name' => 'Pwd User',
        'email' => 'pwd-user@example.com',
        'password' => bcrypt('oldpassword'),
        'role' => 'user',
    ]);

    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/change-password', [
            'oldPassword' => 'oldpassword',
            'newPassword' => 'newpassword123',
            'newPassword_confirmation' => 'newpassword123',
        ])->assertOk()
        ->assertJsonPath('message', 'Password updated successfully.');

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();

    $this->postJson('/api/login', [
        'email' => 'pwd-user@example.com',
        'password' => 'oldpassword',
    ])->assertStatus(422);

    $this->postJson('/api/login', [
        'email' => 'pwd-user@example.com',
        'password' => 'newpassword123',
    ])->assertOk();
});
