<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('LoginController', function () {
    test('can login with email and password', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'test@example.com',
                    ]
                ]
            ]);

        expect($response->json('data.token'))->not->toBeNull();
    });

    test('can login with phone number and password', function () {
        $user = User::factory()->create([
            'phone_number' => '243979575151',
            'country_code' => 243,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => '+243979575151',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                    ]
                ]
            ]);
    });

    test('login fails with incorrect password', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => 'test@example.com',
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    });

    test('login fails with non-existent user', function () {
        $response = $this->postJson('/api/login', [
            'login' => 'nonexistent@example.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
            ]);
    });

    test('validation fails when login is missing', function () {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['login']);
    });

    test('validation fails when password is missing', function () {
        $response = $this->postJson('/api/login', [
            'login' => 'test@example.com',
            'device_name' => 'test-device',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    test('validation fails when device_name is missing', function () {
        $response = $this->postJson('/api/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['device_name']);
    });

    test('can get authenticated user info', function () {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ]
            ]);
    });

    test('me endpoint requires authentication', function () {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    });

    test('creates a new token on each login', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response1 = $this->postJson('/api/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'device-1',
        ]);

        $response2 = $this->postJson('/api/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'device-2',
        ]);

        $token1 = $response1->json('data.token');
        $token2 = $response2->json('data.token');

        expect($token1)->not->toBe($token2);
        expect($user->tokens()->count())->toBe(2);
    });
});

describe('LogoutController', function () {
    test('can logout and delete token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-device');

        $this->actingAs($user, 'sanctum');

        // Confirm user has token
        expect($user->tokens()->count())->toBe(1);

        $response = $this->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        // Verify token is deleted
        $user->refresh();
        expect($user->tokens()->count())->toBe(0);
    });

    test('logout requires authentication', function () {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    });

    test('logout only deletes current token', function () {
        $user = User::factory()->create();
        $token1 = $user->createToken('device-1');
        $token2 = $user->createToken('device-2');

        expect($user->tokens()->count())->toBe(2);

        // Login with first token
        $this->actingAs($user, 'sanctum');
        $this->withToken($token1->plainTextToken);

        $response = $this->postJson('/api/logout');

        $response->assertOk();

        // Only one token should remain
        $user->refresh();
        expect($user->tokens()->count())->toBe(1);
    });

    test('can logout multiple times from different devices', function () {
        $user = User::factory()->create();
        $token1 = $user->createToken('device-1');
        $token2 = $user->createToken('device-2');

        expect($user->tokens()->count())->toBe(2);

        // Logout from device 1
        $this->actingAs($user, 'sanctum');
        $this->withToken($token1->plainTextToken);
        $response1 = $this->postJson('/api/logout');
        $response1->assertOk();

        // Logout from device 2
        $this->withToken($token2->plainTextToken);
        $response2 = $this->postJson('/api/logout');
        $response2->assertOk();

        // All tokens should be deleted
        $user->refresh();
        expect($user->tokens()->count())->toBe(0);
    });
});
