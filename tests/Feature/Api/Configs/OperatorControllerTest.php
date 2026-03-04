<?php

declare(strict_types=1);

use App\Models\Country;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
    Storage::fake('public');
});

describe('OperatorController', function () {
    test('can list all operators', function () {
        $country = Country::factory()->create();
        Operator::factory()->count(5)->create(['country_id' => $country->id]);

        $response = $this->getJson('/api/operators');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'country_id', 'logo_url']
                ]
            ])
            ->assertJsonCount(5, 'data');
    });

    test('can create an operator without logo', function () {
        $country = Country::factory()->create();
        
        $operatorData = [
            'name' => 'Vodacom',
            'country_id' => $country->id,
        ];

        $response = $this->postJson('/api/operators', $operatorData);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'country_id', 'logo_url', 'country']
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Vodacom',
                    'country_id' => $country->id
                ]
            ]);

        $this->assertDatabaseHas('operators', [
            'name' => 'Vodacom',
            'country_id' => $country->id
        ]);
    });

    test('can create an operator with logo', function () {
        $country = Country::factory()->create();
        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        $response = $this->postJson('/api/operators', [
            'name' => 'Airtel',
            'country_id' => $country->id,
            'logo' => $logo,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Airtel',
                    'country_id' => $country->id
                ]
            ]);

        $operator = Operator::where('name', 'Airtel')->first();
        expect($operator->logo)->not->toBeNull();
    });

    test('validation fails when creating operator without required fields', function () {
        $response = $this->postJson('/api/operators', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'country_id']);
    });

    test('validation fails when country_id does not exist', function () {
        $response = $this->postJson('/api/operators', [
            'name' => 'Test Operator',
            'country_id' => 999,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['country_id']);
    });

    test('validation fails when logo is not an image', function () {
        $country = Country::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf');

        $response = $this->postJson('/api/operators', [
            'name' => 'Test Operator',
            'country_id' => $country->id,
            'logo' => $file,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    });

    test('can show a specific operator with country', function () {
        $country = Country::factory()->create(['name' => 'Rwanda']);
        $operator = Operator::factory()->create([
            'name' => 'MTN Rwanda',
            'country_id' => $country->id
        ]);

        $response = $this->getJson("/api/operators/{$operator->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $operator->id,
                    'name' => 'MTN Rwanda',
                    'country_id' => $country->id,
                    'country' => [
                        'id' => $country->id,
                        'name' => 'Rwanda'
                    ]
                ]
            ]);
    });

    test('returns 404 when showing non-existent operator', function () {
        $response = $this->getJson('/api/operators/999');

        $response->assertNotFound();
    });

    test('can update an operator without changing logo', function () {
        $country = Country::factory()->create();
        $newCountry = Country::factory()->create(['name' => 'Uganda']);
        $operator = Operator::factory()->create([
            'name' => 'Old Name',
            'country_id' => $country->id
        ]);

        $response = $this->putJson("/api/operators/{$operator->id}", [
            'name' => 'New Name',
            'country_id' => $newCountry->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'New Name',
                    'country_id' => $newCountry->id
                ]
            ]);

        $this->assertDatabaseHas('operators', [
            'id' => $operator->id,
            'name' => 'New Name',
            'country_id' => $newCountry->id
        ]);
    });

    test('can update an operator with new logo', function () {
        $country = Country::factory()->create();
        $operator = Operator::factory()->create([
            'name' => 'Test Operator',
            'country_id' => $country->id
        ]);

        $newLogo = UploadedFile::fake()->image('new-logo.png', 200, 200);

        $response = $this->putJson("/api/operators/{$operator->id}", [
            'name' => 'Test Operator',
            'country_id' => $country->id,
            'logo' => $newLogo,
        ]);

        $response->assertOk();
        
        $operator->refresh();
        expect($operator->logo)->not->toBeNull();
    });

    test('can delete an operator', function () {
        $country = Country::factory()->create();
        $operator = Operator::factory()->create(['country_id' => $country->id]);

        $response = $this->deleteJson("/api/operators/{$operator->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('operators', ['id' => $operator->id]);
    });

    test('can search operators by name', function () {
        $country = Country::factory()->create();
        Operator::factory()->create(['name' => 'Vodacom', 'country_id' => $country->id]);
        Operator::factory()->create(['name' => 'Airtel', 'country_id' => $country->id]);
        Operator::factory()->create(['name' => 'MTN', 'country_id' => $country->id]);

        $response = $this->getJson('/api/operators?search=Vodacom');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Vodacom');
    });

    test('can paginate operators', function () {
        $country = Country::factory()->create();
        Operator::factory()->count(25)->create(['country_id' => $country->id]);

        $response = $this->getJson('/api/operators?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ])
            ->assertJsonCount(10, 'data');
    });

    test('requires authentication', function () {
        auth()->guard('sanctum')->logout();
        
        $response = $this->getJson('/api/operators');

        $response->assertUnauthorized();
    });
});
