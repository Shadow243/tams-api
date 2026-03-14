<?php

declare(strict_types=1);

return [
    // General
    'page_title' => 'Gestion des Portefeuilles',
    'page_description' => 'Gérer la configuration des portefeuilles télécom',
    'add_new_wallet' => 'Ajouter un Portefeuille',
    'addWallet' => 'Ajouter un Portefeuille',
    'editWallet' => 'Modifier le Portefeuille',
    'delete' => 'Supprimer',
    'edit' => 'Modifier',
    'activate' => 'Activer',
    'deactivate' => 'Désactiver',
    'refresh' => 'Actualiser',
    'searchPlaceholder' => 'Rechercher des portefeuilles...',
    'allStatuses' => 'Tous les Statuts',
    'allBranches' => 'Toutes les Agences',
    'allOperators' => 'Tous les Opérateurs',
    'export' => 'Exporter',
    'exportCSV' => 'Exporter CSV',
    'exportPDF' => 'Exporter PDF',
    'exporting' => 'Exportation...',
    'showing' => 'Affichage de',
    'to' => 'à',
    'of' => 'sur',
    'wallets' => 'portefeuilles',
    'noResults' => 'Aucun portefeuille trouvé',
    'deleteConfirmMessage' => 'Êtes-vous sûr de vouloir supprimer ce portefeuille ?',
    'deleteConfirmTitle' => 'Confirmation de Suppression',
    'toggleStatusMessage' => 'Êtes-vous sûr de vouloir changer le statut de ce portefeuille ?',
    'toggleStatusTitle' => 'Changement de Statut',
    'yes' => 'Oui, procéder',
    'no' => 'Annuler',

    // Status
    'status' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    // Table
    'table' => [
        'wallet_number' => 'Numéro Portefeuille',
        'branch' => 'Agence',
        'operator' => 'Opérateur',
        'balance' => 'Solde',
        'currency' => 'Devise',
        'status' => 'Statut',
        'createdAt' => 'Créé le',
        'actions' => 'Actions',
    ],

    // Form
    'form' => [
        'branch' => 'Agence',
        'selectBranch' => 'Sélectionner une agence',
        'operator' => 'Opérateur',
        'selectOperator' => 'Sélectionner un opérateur',
        'wallet_number' => 'Numéro Portefeuille',
        'wallet_numberPlaceholder' => '1234567890',
        'balance' => 'Solde',
        'balancePlaceholder' => '0.00',
        'currency' => 'Devise',
        'selectCurrency' => 'Sélectionner une devise',
        'currencyPlaceholder' => 'USD',
        'status' => 'Statut',
        'cancel' => 'Annuler',
        'update' => 'Mettre à jour',
        'create' => 'Créer',
    ],

    // Validation
    'validation' => [
        'wallet_number_unique' => 'Ce numéro de portefeuille existe déjà',
        'branch_exists' => 'L\'agence sélectionnée n\'existe pas',
        'operator_exists' => 'L\'opérateur sélectionné n\'existe pas',
        'currency_exists' => 'La devise sélectionnée n\'existe pas',
    ],
];
