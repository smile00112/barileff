<?php

use Webkul\ManagerApp\Tests\ManagerAppTestCase;
use Webkul\User\Models\Admin;

uses(ManagerAppTestCase::class);

it('returns 401 for invalid credentials', function () {
    $response = $this->postJson('/manager/api/auth/login', [
        'email'    => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

it('returns token on valid manager login', function () {
    $admin = $this->createManager();

    $response = $this->postJson('/manager/api/auth/login', [
        'email'    => $admin->email,
        'password' => 'password', // AdminFactory uses bcrypt('password')
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token', 'admin' => ['id', 'name', 'email']]);
});

it('returns 403 when admin has no inventory source assigned', function () {
    $admin = Admin::factory()->create(['status' => 1]);

    $response = $this->postJson('/manager/api/auth/login', [
        'email'    => $admin->email,
        'password' => 'password',
    ]);

    $response->assertStatus(403);
});

it('returns me info when authenticated', function () {
    [$admin, $token] = $this->loginAsManager();

    $response = $this->withToken($token)->getJson('/manager/api/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('id', $admin->id)
        ->assertJsonStructure(['inventory_sources']);
});

it('returns 401 when accessing me without token', function () {
    $response = $this->getJson('/manager/api/auth/me');

    $response->assertStatus(401);
});

it('logs out and revokes token', function () {
    [$admin, $token] = $this->loginAsManager();

    $this->withToken($token)->postJson('/manager/api/auth/logout')->assertStatus(200);

    // Token is revoked — me should now return 401
    $this->withToken($token)->getJson('/manager/api/auth/me')->assertStatus(401);
});
