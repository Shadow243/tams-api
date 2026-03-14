<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Country;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get country IDs
        $countries = Country::all()->keyBy('code');

        $currencies = [
            [
                'code' => 'CDF',
                'name' => 'Franc Congolais',
                'symbol' => 'FC',
                'country_id' => $countries->get('243')?->id,
                'decimal_places' => 0,
                'exchange_rate' => 1.000000,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'code' => 'USD',
                'name' => 'Dollar Américain',
                'symbol' => '$',
                'country_id' => $countries->get('1')?->id,
                'decimal_places' => 2,
                'exchange_rate' => 0.00041, // ~2450 CDF = 1 USD (exemple)
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'country_id' => $countries->get('33')?->id,
                'decimal_places' => 2,
                'exchange_rate' => 0.00037, // ~2700 CDF = 1 EUR (exemple)
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'UGX',
                'name' => 'Shilling Ougandais',
                'symbol' => 'USh',
                'country_id' => $countries->get('256')?->id,
                'decimal_places' => 0,
                'exchange_rate' => 1.52, // ~1 CDF = 1.52 UGX (exemple)
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'RWF',
                'name' => 'Franc Rwandais',
                'symbol' => 'FRw',
                'country_id' => $countries->get('250')?->id,
                'decimal_places' => 0,
                'exchange_rate' => 0.50, // ~1 CDF = 0.5 RWF (exemple)
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'BIF',
                'name' => 'Franc Burundais',
                'symbol' => 'FBu',
                'country_id' => $countries->get('257')?->id,
                'decimal_places' => 0,
                'exchange_rate' => 1.15, // ~1 CDF = 1.15 BIF (exemple)
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'KES',
                'name' => 'Shilling Kenyan',
                'symbol' => 'KSh',
                'country_id' => $countries->get('254')?->id,
                'decimal_places' => 2,
                'exchange_rate' => 0.0091, // ~110 CDF = 1 KES (exemple)
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'TZS',
                'name' => 'Shilling Tanzanien',
                'symbol' => 'TSh',
                'country_id' => $countries->get('255')?->id,
                'decimal_places' => 2,
                'exchange_rate' => 0.00043, // ~2300 CDF = 1 TZS (exemple)
                'is_active' => true,
                'is_default' => false,
            ]
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                array_merge($currency, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('Devises créées/mises à jour avec succès.');
    }
}
