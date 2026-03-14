<?php

declare(strict_types=1);

return [
    // General
    'page_title' => 'Currency Management',
    'page_description' => 'Manage currencies supported by the system',
    'add_currency' => 'Add Currency',
    'edit_currency' => 'Edit Currency',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    'refresh' => 'Refresh',
    'search_placeholder' => 'Search currency...',
    'no_results' => 'No currencies found',
    'loading' => 'Loading currencies...',
    'no_currencies' => 'No currencies configured',
    
    // Messages
    'created_successfully' => 'Currency created successfully',
    'updated_successfully' => 'Currency updated successfully',
    'deleted_successfully' => 'Currency deleted successfully',
    'create_error' => 'Error creating currency',
    'update_error' => 'Error updating currency',
    'delete_error' => 'Error deleting currency',
    'not_found' => 'Currency not found',
    'in_use' => 'This currency is being used in transactions and cannot be deleted',
    'used_by_wallets' => 'This currency is being used by wallets and cannot be deleted',
    'cannot_delete_default' => 'The default currency cannot be deleted',
    'no_default_currency' => 'No default currency configured',
    
    // Confirmation
    'delete_confirm_title' => 'Delete Currency?',
    'delete_confirm_message' => 'Are you sure you want to delete this currency? This action cannot be undone.',
    'delete_confirm_button' => 'Yes, delete it',
    'cancel_button' => 'Cancel',
    
    // Status
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    
    // Table
    'table' => [
        'code' => 'Code',
        'name' => 'Name',
        'symbol' => 'Symbol',
        'exchange_rate' => 'Exchange Rate',
        'decimals' => 'Decimals',
        'status' => 'Status',
        'default' => 'Default',
        'example' => 'Example',
        'actions' => 'Actions',
    ],
    
    // Form
    'form' => [
        'code' => 'ISO Code',
        'code_placeholder' => 'Ex: USD, EUR, CDF',
        'code_hint' => '3-letter ISO code (Ex: USD, EUR, CDF)',
        'name' => 'Name',
        'name_placeholder' => 'Ex: US Dollar',
        'symbol' => 'Symbol',
        'symbol_placeholder' => 'Ex: $, €, FC',
        'country' => 'Country',
        'country_placeholder' => 'Select a country',
        'country_hint' => 'Country of origin of the currency (optional)',
        'decimal_places' => 'Decimal Places',
        'decimal_places_hint' => 'Number of digits after decimal point (0-4)',
        'exchange_rate' => 'Exchange Rate',
        'exchange_rate_placeholder' => 'Ex: 1.0',
        'exchange_rate_hint' => 'Exchange rate relative to base currency',
        'is_active' => 'Active',
        'is_active_hint' => 'Currency is available for transactions',
        'is_default' => 'Default',
        'is_default_hint' => 'Set as system default currency',
        'cancel' => 'Cancel',
        'update' => 'Update',
        'create' => 'Create',
    ],
    
    // Validation
    'validation' => [
        'code_required' => 'Currency code is required',
        'code_size' => 'Currency code must be exactly 3 characters',
        'code_uppercase' => 'Currency code must be uppercase',
        'code_unique' => 'This currency code already exists',
        'name_required' => 'Currency name is required',
        'name_max' => 'Currency name cannot exceed 100 characters',
        'symbol_required' => 'Currency symbol is required',
        'symbol_max' => 'Symbol cannot exceed 10 characters',
        'country_id_integer' => 'Country ID must be an integer',
        'country_id_exists' => 'Selected country does not exist',
        'decimal_places_required' => 'Decimal places is required',
        'decimal_places_integer' => 'Decimal places must be an integer',
        'decimal_places_min' => 'Decimal places must be at least 0',
        'decimal_places_max' => 'Decimal places cannot exceed 4',
        'exchange_rate_required' => 'Exchange rate is required',
        'exchange_rate_numeric' => 'Exchange rate must be a number',
        'exchange_rate_min' => 'Exchange rate must be greater than 0',
        'exchange_rate_max' => 'Exchange rate is too high',
        'is_active_boolean' => 'Active status must be true or false',
        'is_default_boolean' => 'Default status must be true or false',
    ],
];
