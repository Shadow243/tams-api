<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

describe('CountryController', function () {
    test('can list all countries', function () {
        Country::factory()->count(5)->create();

        $response = $this->getJson('/api/countries');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'code']
                ]
            ])
            ->assertJsonCount(5, 'data');
    });

    test('can create a country', function () {
        $countryData = [
            'name' => 'Democratic Republic of Congo',
            'code' => 'CD',
        ];

        $response = $this->postJson('/api/countries', $countryData);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'code']
            ])
            ->assertJson([
                'success' => true,
                'data' => $countryData
            ]);

        $this->assertDatabaseHas('countries', $countryData);
    });

    test('validation fails when creating country without required fields', function () {
        $response = $this->postJson('/api/countries', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'code']);
    });

    test('validation fails when country code is not 2 characters', function () {
        $response = $this->postJson('/api/countries', [
            'name' => 'Test Country',
            'code' => 'ABC',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    });

    test('can show a specific country', function () {
        $country = Country::factory()->create([
            'name' => 'Rwanda',
            'code' => 'RW'
        ]);

        $response = $this->getJson("/api/countries/{$country->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $country->id,
                    'name' => 'Rwanda',
                    'code' => 'RW'
                ]
            ]);
    });

    test('returns 404 when showing non-existent country', function () {
        $response = $this->getJson('/api/countries/999');

        $response->assertNotFound();
    });

    test('can update a country', function () {
        $country = Country::factory()->create([
            'name' => 'Old Name',
            'code' => 'ON'
        ]);

        $updatedData = [
            'name' => 'New Name',
            'code' => 'NN',
        ];

        $response = $this->putJson("/api/countries/{$country->id}", $updatedData);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => $updatedData
            ]);

        $this->assertDatabaseHas('countries', array_merge(['id' => $country->id], $updatedData));
    });

    test('can delete a country', function () {
        $country = Country::factory()->create();

        $response = $this->deleteJson("/api/countries/{$country->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    });

    test('can search countries by name', function () {
        Country::factory()->create(['name' => 'Rwanda', 'code' => 'RW']);
        Country::factory()->create(['name' => 'Uganda', 'code' => 'UG']);
        Country::factory()->create(['name' => 'Kenya', 'code' => 'KE']);

        $response = $this->getJson('/api/countries?search=Rwanda');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Rwanda');
    });

    test('can paginate countries', function () {
        Country::factory()->count(25)->create();

        $response = $this->getJson('/api/countries?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ])
            ->assertJsonCount(10, 'data');
    });

    test('requires authentication', function () {
        auth()->guard('sanctum')->logout();
        
        $response = $this->getJson('/api/countries');

        $response->assertUnauthorized();
    });
});
