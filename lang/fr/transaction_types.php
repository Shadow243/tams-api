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
    'name' => 'Nom',
    'description' => 'Description',

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
