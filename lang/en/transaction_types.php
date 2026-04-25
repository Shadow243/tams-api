<?php

return [
    'page_title' => 'Transaction Types',
    'add_transaction_type' => 'Add Transaction Type',
    'edit_transaction_type' => 'Edit Transaction Type',
    'delete_transaction_type' => 'Delete Transaction Type',
    'transaction_type_details' => 'Transaction Type Details',
    'export_subtitle' => 'List of Transaction Types',
    'generated_on' => 'Generated on',
    'no_records' => 'No transaction types found',

    // Form Fields
    'code' => 'Code',
    'code_hint' => 'Use only lowercase letters, numbers and underscores',
    'name' => 'Name',
    'description' => 'Description',
    'cancel' => 'Cancel',
    'create' => 'Create',
    'update' => 'Update',

    // Balance Configuration
    'balance_config'      => 'Balance Configuration',
    'balance_config_hint' => 'Defines how balances are affected when this transaction is completed.',
    'branch_source'       => 'Source Branch',
    'branch_destination'  => 'Destination Branch',
    'wallet'              => 'Source Wallet',
    'wallet_destination'  => 'Destination Wallet',
    'effect'              => 'Effect',
    'effect_none'         => 'None',
    'effect_debit'        => 'Debit (loses cash)',
    'effect_credit'       => 'Credit (gains cash)',
    'amount'              => 'Amount applied',
    'amount_gross'        => 'Gross amount',
    'amount_net'          => 'Net amount (after fees)',
    'amount_fee'          => 'Fee only',

    // Table Headers
    'table' => [
        'code' => 'Code',
        'name' => 'Name',
        'description' => 'Description',
        'actions' => 'Actions',
    ],

    // Actions
    'edit' => 'Edit',
    'delete' => 'Delete',
    'view' => 'View',
    'export' => 'Export',
    'search_placeholder' => 'Search by code, name or description...',

    // Validation Messages
    'validation' => [
        'code_required' => 'The code field is required.',
        'code_unique' => 'This code already exists.',
        'code_format' => 'The code must contain only lowercase letters, numbers and underscores.',
        'name_required' => 'The name field is required.',
        'name_max' => 'The name must not exceed 255 characters.',
        'description_max' => 'The description must not exceed 1000 characters.',
    ],

    // Transaction Type Codes
    'cash_deposit_transfer' => 'Cash Deposit Transfer',
    'cash_withdraw_transfer' => 'Cash Withdraw Transfer',
    'wallet_cash_in' => 'Wallet Cash In',
    'wallet_cash_out' => 'Wallet Cash Out',
    'tams_deposit' => 'TAMS Deposit',
    'tams_withdraw' => 'TAMS Withdraw',
];
