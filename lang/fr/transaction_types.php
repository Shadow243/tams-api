<?php

return [
    'page_title' => 'Types d\'Opérations',
    'add_transaction_type' => 'Ajouter un Type d\'Opération',
    'edit_transaction_type' => 'Modifier le Type d\'Opération',
    'delete_transaction_type' => 'Supprimer le Type d\'Opération',
    'transaction_type_details' => 'Détails du Type d\'Opération',
    'export_subtitle' => 'Liste des Types d\'Opérations',
    'generated_on' => 'Généré le',
    'no_records' => 'Aucun type d\'opération trouvé',

    // Form Fields
    'code' => 'Code',
    'code_hint' => 'Lettres minuscules, chiffres et underscores uniquement',
    'name' => 'Nom',
    'description' => 'Description',
    'cancel' => 'Annuler',
    'create' => 'Créer',
    'update' => 'Modifier',

    // Balance Configuration
    'balance_config'      => 'Configuration des soldes',
    'balance_config_hint' => 'Définit comment les soldes sont affectés lorsque cette transaction est complétée.',
    'branch_source'       => 'Agence source',
    'branch_destination'  => 'Agence destination',
    'wallet'              => 'Wallet source',
    'wallet_destination'  => 'Wallet destination',
    'effect'              => 'Effet',
    'effect_none'         => 'Aucun',
    'effect_debit'        => 'Débit (perd du cash)',
    'effect_credit'       => 'Crédit (reçoit du cash)',
    'amount'              => 'Montant appliqué',
    'amount_gross'        => 'Montant brut',
    'amount_net'          => 'Montant net (après frais)',
    'amount_fee'          => 'Frais uniquement',

    // Table Headers
    'table' => [
        'code' => 'Code',
        'name' => 'Nom',
        'description' => 'Description',
        'actions' => 'Actions',
    ],

    // Actions
    'edit' => 'Modifier',
    'delete' => 'Supprimer',
    'view' => 'Voir',
    'export' => 'Exporter',
    'search_placeholder' => 'Rechercher par code, nom ou description...',

    // Validation Messages
    'validation' => [
        'code_required' => 'Le champ code est obligatoire.',
        'code_unique' => 'Ce code existe déjà.',
        'code_format' => 'Le code doit contenir uniquement des lettres minuscules, des chiffres et des underscores.',
        'name_required' => 'Le champ nom est obligatoire.',
        'name_max' => 'Le nom ne doit pas dépasser 255 caractères.',
        'description_max' => 'La description ne doit pas dépasser 1000 caractères.',
    ],

    // Transaction Type Codes
    'cash_deposit_transfer' => 'Transfert de Dépôt en Espèces',
    'cash_withdraw_transfer' => 'Transfert de Retrait en Espèces',
    'wallet_cash_in' => 'Rechargement Portefeuille',
    'wallet_cash_out' => 'Retrait Portefeuille',
    'tams_deposit' => 'Dépôt TAMS',
    'tams_withdraw' => 'Retrait TAMS',
];
