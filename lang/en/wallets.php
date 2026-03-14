<?php

declare(strict_types=1);

return [
    // General
    'page_title' => 'Wallets Management',
    'page_description' => 'Manage telecom wallets configuration',
    'add_new_wallet' => 'Add New Wallet',
    'addWallet' => 'Add Wallet',
    'editWallet' => 'Edit Wallet',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    'refresh' => 'Refresh',
    'searchPlaceholder' => 'Search wallets...',
    'allStatuses' => 'All Statuses',
    'allBranches' => 'All Branches',
    'allOperators' => 'All Operators',
    'export' => 'Export',
    'exportCSV' => 'Export CSV',
    'exportPDF' => 'Export PDF',
    'exporting' => 'Exporting...',
    'showing' => 'Showing',
    'to' => 'to',
    'of' => 'of',
    'wallets' => 'wallets',
    'noResults' => 'No wallets found',
    'deleteConfirmMessage' => 'Are you sure you want to delete this wallet?',
    'deleteConfirmTitle' => 'Delete Confirmation',
    'toggleStatusMessage' => 'Are you sure you want to change this wallet status?',
    'toggleStatusTitle' => 'Status Change',
    'yes' => 'Yes, proceed',
    'no' => 'Cancel',

    // Status
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    // Table
    'table' => [
        'wallet_number' => 'Wallet Number',
        'branch' => 'Branch',
        'operator' => 'Operator',
        'balance' => 'Balance',
        'currency' => 'Currency',
        'status' => 'Status',
        'createdAt' => 'Created At',
        'actions' => 'Actions',
    ],

    // Form
    'form' => [
        'branch' => 'Branch',
        'selectBranch' => 'Select a branch',
        'operator' => 'Operator',
        'selectOperator' => 'Select an operator',
        'wallet_number' => 'Wallet Number',
        'wallet_numberPlaceholder' => '1234567890',
        'balance' => 'Balance',
        'balancePlaceholder' => '0.00',
        'currency' => 'Currency',
        'selectCurrency' => 'Select a currency',
        'currencyPlaceholder' => 'USD',
        'status' => 'Status',
        'cancel' => 'Cancel',
        'update' => 'Update',
        'create' => 'Create',
    ],

    // Validation
    'validation' => [
        'wallet_number_unique' => 'This wallet number already exists',
        'branch_exists' => 'The selected branch does not exist',
        'operator_exists' => 'The selected operator does not exist',
        'currency_exists' => 'The selected currency does not exist',
    ],
];
