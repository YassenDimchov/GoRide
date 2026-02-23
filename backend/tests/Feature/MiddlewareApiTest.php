<?php

use App\Models\User;

beforeEach(function () {
    resetApiTestData();
});

test('not_suspended middleware blocks suspended user and deletes current token', function () {
    $user = User::create([
        'name' => 'Suspended Middleware',
        'email' => 'suspended-mw@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
        'suspended' => true,
    ]);

    $access = $user->createToken('api');
    $token = $access->plainTextToken;
    $tokenId = $access->accessToken->id;

    $this->withToken($token)
        ->getJson('/api/me')
        ->assertStatus(403)
        ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/me')->assertStatus(401);
});
