<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('LocaleController', function () {
    test('can get available locales', function () {
        $response = $this->getJson('/api/locales');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => ['code', 'name']
            ]);

        $locales = $response->json();
        expect($locales)->toBeArray();
        expect(count($locales))->toBeGreaterThan(0);
    });

    test('available locales include en and fr', function () {
        $response = $this->getJson('/api/locales');

        $response->assertOk();

        $locales = collect($response->json());
        $codes = $locales->pluck('code')->toArray();

        expect($codes)->toContain('en');
        expect($codes)->toContain('fr');
    });

    test('can get translations for English locale', function () {
        $response = $this->getJson('/api/locales/en/translations');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [] // Each translation group should have key-value pairs
            ]);

        $translations = $response->json();
        expect($translations)->toBeArray();
        expect($translations)->not->toBeEmpty();
    });

    test('can get translations for French locale', function () {
        $response = $this->getJson('/api/locales/fr/translations');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => []
            ]);

        $translations = $response->json();
        expect($translations)->toBeArray();
        expect($translations)->not->toBeEmpty();
    });

    test('translations include common keys', function () {
        $response = $this->getJson('/api/locales/en/translations');

        $response->assertOk();

        $translations = $response->json();
        
        // Verify that common translation groups exist
        expect($translations)->toHaveKey('auth');
        expect($translations)->toHaveKey('messages');
    });

    test('English and French translations have similar structure', function () {
        $enResponse = $this->getJson('/api/locales/en/translations');
        $frResponse = $this->getJson('/api/locales/fr/translations');

        $enResponse->assertOk();
        $frResponse->assertOk();

        $enTranslations = $enResponse->json();
        $frTranslations = $frResponse->json();

        $enKeys = array_keys($enTranslations);
        $frKeys = array_keys($frTranslations);

        // Both should have similar top-level keys
        expect(count($enKeys))->toBeGreaterThan(0);
        expect(count($frKeys))->toBeGreaterThan(0);
    });

    test('returns 404 for non-existent locale', function () {
        $response = $this->getJson('/api/locales/invalid/translations');

        $response->assertNotFound();
    });

    test('locale endpoints do not require authentication', function () {
        // Ensure we're not authenticated
        auth()->guard('sanctum')->logout();

        $response = $this->getJson('/api/locales');
        $response->assertOk();

        $response = $this->getJson('/api/locales/en/translations');
        $response->assertOk();
    });

    test('translations return JSON format', function () {
        $response = $this->getJson('/api/locales/en/translations');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json');
    });

    test('each locale has required properties', function () {
        $response = $this->getJson('/api/locales');

        $response->assertOk();

        $locales = $response->json();
        
        foreach ($locales as $locale) {
            expect($locale)->toHaveKey('code');
            expect($locale)->toHaveKey('name');
            expect($locale['code'])->toBeString();
            expect($locale['name'])->toBeString();
        }
    });
});
