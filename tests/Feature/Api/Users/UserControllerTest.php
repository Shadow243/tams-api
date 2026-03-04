<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
    Storage::fake('public');
});

describe('UserController', function () {
    test('can list all users', function () {
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'username']
                ]
            ]);
    });

    test('can create a user without avatar', function () {
        $userData = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gender' => 'M',
            'phone_number' => '243979575151',
            'country_code' => 243,
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email', 'username']
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'username' => 'johndoe',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
        ]);
    });

    test('can create a user with avatar', function () {
        $avatar = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $userData = [
            'name' => 'Jane Smith',
            'username' => 'janesmith',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gender' => 'F',
            'phone_number' => '243979575152',
            'country_code' => 243,
            'avatar' => $avatar,
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                ]
            ]);

        $user = User::where('email', 'jane@example.com')->first();
        expect($user->avatar)->not->toBeNull();
    });

    test('password is hashed when creating user', function () {
        $password = 'Password123!';
        
        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'gender' => 'M',
            'phone_number' => '243979575153',
            'country_code' => 243,
        ]);

        $response->assertCreated();

        $user = User::where('email', 'test@example.com')->first();
        expect(Hash::check($password, $user->password))->toBeTrue();
    });

    test('validation fails when creating user without required fields', function () {
        $response = $this->postJson('/api/users', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'username']);
    });

    test('validation fails when email is already taken', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'username' => 'testuser2',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gender' => 'M',
            'phone_number' => '243979575154',
            'country_code' => 243,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('validation fails when username is already taken', function () {
        User::factory()->create(['username' => 'taken']);

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'username' => 'taken',
            'email' => 'newemail@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gender' => 'M',
            'phone_number' => '243979575155',
            'country_code' => 243,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);
    });

    test('validation fails when passwords do not match', function () {
        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
            'gender' => 'M',
            'phone_number' => '243979575156',
            'country_code' => 243,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    test('validation fails when avatar is not an image', function () {
        $file = UploadedFile::fake()->create('document.pdf');

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'gender' => 'M',
            'phone_number' => '243979575157',
            'country_code' => 243,
            'avatar' => $file,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['avatar']);
    });

    test('can show a specific user', function () {
        $user = User::factory()->create([
            'name' => 'Specific User',
            'email' => 'specific@example.com',
        ]);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Specific User',
                    'email' => 'specific@example.com',
                ]
            ]);
    });

    test('returns 404 when showing non-existent user', function () {
        $response = $this->getJson('/api/users/999999');

        $response->assertNotFound();
    });

    test('can update a user without changing password', function () {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'username' => $user->username,
            'gender' => $user->gender,
            'phone_number' => $user->phone_number,
            'country_code' => $user->country_code,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New Name',
                    'email' => 'new@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    });

    test('can update a user with new avatar', function () {
        $user = User::factory()->create();
        $newAvatar = UploadedFile::fake()->image('new-avatar.jpg', 200, 200);

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'gender' => $user->gender,
            'phone_number' => $user->phone_number,
            'country_code' => $user->country_code,
            'avatar' => $newAvatar,
        ]);

        $response->assertOk();
        
        $user->refresh();
        expect($user->avatar)->not->toBeNull();
    });

    test('can delete a user', function () {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'deleted_at' => null
        ]);
    });

    test('can bulk delete users', function () {
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $response = $this->deleteJson('/api/users/bulk', [
            'ids' => $userIds
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        foreach ($userIds as $userId) {
            $this->assertDatabaseMissing('users', [
                'id' => $userId,
                'deleted_at' => null
            ]);
        }
    });

    test('validation fails for bulk delete with invalid ids', function () {
        $response = $this->deleteJson('/api/users/bulk', [
            'ids' => [999999, 888888]
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0', 'ids.1']);
    });

    test('can search users by name', function () {
        User::factory()->create(['name' => 'Alice Johnson']);
        User::factory()->create(['name' => 'Bob Smith']);
        User::factory()->create(['name' => 'Alice Brown']);

        $response = $this->getJson('/api/users?search=Alice');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    test('can paginate users', function () {
        User::factory()->count(25)->create();

        $response = $this->getJson('/api/users?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ])
            ->assertJsonCount(10, 'data');
    });

    test('can export users to PDF', function () {
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users/export/pdf');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    });

    test('requires authentication', function () {
        auth()->guard('sanctum')->logout();
        
        $response = $this->getJson('/api/users');

        $response->assertUnauthorized();
    });
});
