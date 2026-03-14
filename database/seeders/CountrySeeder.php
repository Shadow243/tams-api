<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'code' => '243',
                'name' => 'République Démocratique du Congo',
            ],
            [
                'code' => '1',
                'name' => 'États-Unis',
            ],
            [
                'code' => '33',
                'name' => 'France (Zone Euro)',
            ],
            [
                'code' => '256',
                'name' => 'Ouganda',
            ],
            [
                'code' => '250',
                'name' => 'Rwanda',
            ],
            [
                'code' => '257',
                'name' => 'Burundi',
            ],
            [
                'code' => '254',
                'name' => 'Kenya',
            ],
            [
                'code' => '255',
                'name' => 'Tanzanie',
            ],
        ];

        foreach ($countries as $country) {
            DB::table('countries')->updateOrInsert(
                ['code' => $country['code']],
                array_merge($country, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Pays créés/mis à jour avec succès.');
    }
}
