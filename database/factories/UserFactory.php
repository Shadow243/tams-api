<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name  = fake()->name();
        $user = new \App\Models\User;
        $user->name = $name;
        return [
            'uuid' => $user->newUniqueId(),
            'username' => \App\Models\User::generateUsername($user),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'gender' => fake()->randomElement(['M', 'F']),
            'country_code' => '243',
            'phone_number' => $this->generateDRCPhoneNumber(),
            'timezone' => fake()->timezone(),

        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    private function generateDRCPhoneNumber(): string
    {
        // DRC phone number format with correct prefixes
        $prefixes = [
            '97', '98', '99',
            '84', '85', '89',
            '81', '82',
        ];

        $prefix = fake()->randomElement($prefixes);
        $remainingDigits = fake()->numberBetween(1000000, 9999999);

        return $prefix . $remainingDigits;
    }

}
