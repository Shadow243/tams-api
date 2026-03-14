<?php

namespace Database\Seeders;

use App\Models\TransactionType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TypeTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactionTypes = [
            [
                'code' => 'cash_deposit_transfer',
                'name' => 'Dépôt pour retrait ultérieur',
                'description' => 'Dépôt cash récupérable plus tard',
            ],
            [
                'code' => 'cash_withdraw_transfer',
                'name' => 'Retrait d\'un transfert',
                'description' => 'Retrait d\'un dépôt existant',
            ],
            [
                'code' => 'wallet_cash_in',
                'name' => 'Réception wallet vers cash',
                'description' => 'Client envoie wallet → reçoit cash',
            ],
            [
                'code' => 'wallet_cash_out',
                'name' => 'Envoi cash vers wallet',
                'description' => 'Client donne cash → reçoit wallet',
            ],
            [
                'code' => 'tams_deposit',
                'name' => 'Dépôt Compte TAMS',
                'description' => 'Dépôt sans frais',
            ],
            [
                'code' => 'tams_withdraw',
                'name' => 'Retrait Compte TAMS',
                'description' => 'Retrait sans frais',
            ],
            [
                'code' => 'tams_debt_withdraw',
                'name' => 'Retrait à découvert TAMS',
                'description' => 'Génère dette',
            ],
            [
                'code' => 'wallet_to_wallet_transfer',
                'name' => 'Transfert wallet vers numéro',
                'description' => 'Envoi wallet simple',
            ],
            [
                'code' => 'wallet_receive_only',
                'name' => 'Réception wallet simple',
                'description' => 'Réception wallet sans cash',
            ],
        ];

        foreach ($transactionTypes as $transactionType) {
            TransactionType::updateOrCreate(
                ['code' => $transactionType['code']],
                array_merge($transactionType, [
                    'uuid' => TransactionType::where('code', $transactionType['code'])->value('uuid') 
                        ?? (string) Str::orderedUuid()
                ])
            );
        }

        $this->command->info('Types de transactions créés/mis à jour avec succès.');
    }
}
