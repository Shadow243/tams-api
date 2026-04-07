<?php

declare(strict_types=1);

return [
    // Page
    'page_title' => 'Transactions',
    'page_description' => 'Manage all transactions',
    
    // Actions
    'add_transaction' => 'Add Transaction',
    'edit_transaction' => 'Edit Transaction',
    'view_transaction' => 'View Transaction',
    'delete_transaction' => 'Delete Transaction',
    'cancel_transaction' => 'Cancel Transaction',
    'complete_transaction' => 'Complete Transaction',
    'statistics' => 'Statistics',
    'close' => 'Close',
    'cancel' => 'Cancel',
    'save' => 'Save',
    'update' => 'Update',
    'create' => 'Create',
    'refresh' => 'Refresh',
    
    // Status
    'pending' => 'Pending',
    'available' => 'Available',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'failed' => 'Failed',
    'expired' => 'Expired',
    
    // Fields
    'transaction_type' => 'Transaction Type',
    'branch' => 'Branch',
    'destination_branch' => 'Destination Branch',
    'customer' => 'Customer',
    'wallet' => 'Wallet',
    'customer_phone' => 'Customer Phone',
    'gross_amount' => 'Gross Amount',
    'fee_amount' => 'Fee Amount',
    'net_amount' => 'Net Amount',
    'status' => 'Status',
    'reference' => 'Reference',
    'currency' => 'Currency',
    'period' => 'Period',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'date_range' => 'Date Range',
    'created_by' => 'Created By',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    
    // Periods
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'this_week' => 'This Week',
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
    'this_year' => 'This Year',
    'custom' => 'Custom',
    
    // Statistics
    'by_status' => 'By Status',
    'overview' => 'Overview',
    'total_transactions' => 'Total Transactions',
    'total_amount' => 'Total Amount',
    'total_fees' => 'Total Fees',
    'total_net' => 'Net Total',
    'no_statistics' => 'No statistics available',
    'loading_stats' => 'Loading statistics...',
    'stats_error' => 'Error loading statistics',
    
    // Details
    'transaction_details' => 'Transaction Details',
    'status_timeline' => 'Transaction Status',
    'additional_info' => 'Additional Information',
    'loading_details' => 'Loading details...',
    'fetch_error' => 'Error loading details',
    
    // Confirmation Messages
    'confirm_delete_title' => 'Are you sure?',
    'confirm_delete_text' => 'This action cannot be undone!',
    'confirm_delete_button' => 'Yes, delete it!',
    'confirm_cancel_title' => 'Cancel transaction?',
    'confirm_cancel_text' => 'This action cannot be undone!',
    'confirm_cancel_button' => 'Yes, cancel it!',
    'confirm_complete_title' => 'Complete Transaction?',
    'confirm_complete_text' => 'This will mark the transaction as completed.',
    'confirm_complete_button' => 'Yes, complete it!',
    
    // Success Messages
    'success' => 'Success!',
    'create_success' => 'Transaction created successfully.',
    'update_success' => 'Transaction updated successfully.',
    'delete_success' => 'Transaction deleted successfully.',
    'cancel_success' => 'Transaction cancelled successfully.',
    'complete_success' => 'Transaction completed successfully.',
    
    // Error Messages
    'error' => 'Error!',
    'create_error' => 'Failed to create transaction.',
    'update_error' => 'Failed to update transaction.',
    'delete_error' => 'Failed to delete transaction.',
    'cancel_error' => 'Failed to cancel transaction.',
    'complete_error' => 'Failed to complete transaction.',
];
