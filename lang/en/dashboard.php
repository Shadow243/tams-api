<?php

declare(strict_types=1);

return [
    // Page
    'title'       => 'Dashboard',
    'description' => 'Global overview of the transfer system',

    // KPI labels
    'total'             => 'Total',
    'total_transactions' => 'Transactions',
    'total_amount'      => 'Total Amount',
    'total_fees'        => 'Total Fees',
    'total_net'         => 'Net Amount',
    'average_amount'    => 'Average Amount',
    'success_rate'      => 'Success Rate',
    'gross'             => 'Gross',
    'fees'              => 'Fees',
    'net'               => 'Net',
    'rate'              => 'Rate',
    'average'           => 'Avg',
    'amount'            => 'Amount',

    // Sections
    'by_status'           => 'Status Breakdown',
    'trend'               => 'Transaction Trends',
    'by_type'             => 'By Transaction Type',
    'by_branch'           => 'By Branch',
    'recent_transactions' => 'Recent Transactions',
    'view_all'            => 'View All',

    // Filters
    'filter' => [
        'all_branches'   => 'All Branches',
        'all_currencies' => 'All Currencies',
        'all_types'      => 'All Types',
    ],

    // Periods
    'period' => [
        'today'      => 'Today',
        'yesterday'  => 'Yesterday',
        'week'       => 'This Week',
        'month'      => 'This Month',
        'last_month' => 'Last Month',
        'year'       => 'This Year',
        'all'        => 'All Time',
        'custom'     => 'Custom Range',
    ],

    // Balance Report
    'balance_report'       => 'Balance Report',
    'balance_report_desc'  => 'Cash balances per branch and virtual balances per wallet',
    'total_branch_cash'    => 'Branch Cash',
    'total_wallet_virtual' => 'Wallet Virtual',
    'branch_balances'      => 'Branch Balances (Cash)',
    'wallet_balances'      => 'Wallet Balances (Virtual)',
    'no_balance_data'      => 'No balance recorded',
    'no_wallets'           => 'No wallets',
    'branch'               => 'Branch',
    'currency'             => 'Currency',
    'cash_balance'         => 'Cash Balance',
    'wallet'               => 'Number',
    'operator'             => 'Operator',
    'virtual_balance'      => 'Virtual Balance',
    'confirmed'            => 'Confirmed',
    'pending'              => 'Pending',
    'projected'            => 'Projected',
    'system_totals'        => 'System Totals',
    'all_branches'         => 'All Branches',

    // UI States
    'filters_active' => 'active filter(s)',
    'reset_filters'  => 'Reset',
    'no_data'        => 'No data available',
    'no_trend_data'  => 'No trend data available',
    'no_recent'      => 'No recent transactions',
    'no_data_title'  => 'No data available',
    'no_data_desc'   => 'Adjust the filters or check your connection.',
    'retry'          => 'Retry',
];
