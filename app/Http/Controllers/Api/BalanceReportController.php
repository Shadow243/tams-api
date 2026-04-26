<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class BalanceReportController extends Controller
{
    public function index()
    {
        // Calculate pending balance impacts from pending transactions
        $pendingImpacts = $this->calculatePendingImpacts();

        // Get branches with current balances
        $branches = Branch::with(['balances.currency'])
            ->orderBy('name')
            ->get()
            ->map(function($branch) use ($pendingImpacts) {
                return [
                    'id'       => $branch->id,
                    'name'     => $branch->name,
                    'code'     => $branch->code,
                    'status'   => $branch->status->value,
                    'balances' => $branch->balances->map(function($b) use ($branch, $pendingImpacts) {
                        $currencyCode = $b->currency?->code ?? $b->currency_code;
                        $pendingAmount = $pendingImpacts['branches'][$branch->id][$currencyCode] ?? 0.0;
                        $confirmedBalance = (float) $b->cash_balance;
                        
                        return [
                            'currency_code'      => $currencyCode,
                            'currency_name'      => $b->currency?->name,
                            'currency_symbol'    => $b->currency?->symbol,
                            'balance_confirmed'  => $confirmedBalance,
                            'balance_pending'    => $pendingAmount,
                            'balance_projected'  => $confirmedBalance + $pendingAmount,
                        ];
                    })->values(),
                ];
            });

        // Get wallets with current balances
        $wallets = Wallet::with(['branch:id,name', 'operator:id,name', 'currency:id,code,symbol,name'])
            ->orderBy('wallet_number')
            ->get()
            ->map(function($wallet) use ($pendingImpacts) {
                $currencyCode = $wallet->currency?->code;
                $pendingAmount = $pendingImpacts['wallets'][$wallet->id][$currencyCode] ?? 0.0;
                $confirmedBalance = (float) $wallet->virtual_balance;
                
                return [
                    'id'                 => $wallet->id,
                    'wallet_number'      => $wallet->wallet_number,
                    'operator_name'      => $wallet->operator?->name,
                    'branch_name'        => $wallet->branch?->name,
                    'currency_code'      => $currencyCode,
                    'currency_symbol'    => $wallet->currency?->symbol,
                    'balance_confirmed'  => $confirmedBalance,
                    'balance_pending'    => $pendingAmount,
                    'balance_projected'  => $confirmedBalance + $pendingAmount,
                    'status'             => $wallet->status->value,
                ];
            });

        // Aggregate totals by currency
        $summary = [];

        foreach ($branches as $branch) {
            foreach ($branch['balances'] as $bal) {
                $code = $bal['currency_code'];
                $summary[$code] ??= [
                    'currency_code'               => $code,
                    'currency_symbol'             => $bal['currency_symbol'],
                    'total_branch_confirmed'      => 0.0,
                    'total_branch_pending'        => 0.0,
                    'total_branch_projected'      => 0.0,
                    'total_wallet_confirmed'      => 0.0,
                    'total_wallet_pending'        => 0.0,
                    'total_wallet_projected'      => 0.0,
                    'total_system_confirmed'      => 0.0,
                    'total_system_pending'        => 0.0,
                    'total_system_projected'      => 0.0,
                ];
                $summary[$code]['total_branch_confirmed'] += $bal['balance_confirmed'];
                $summary[$code]['total_branch_pending'] += $bal['balance_pending'];
                $summary[$code]['total_branch_projected'] += $bal['balance_projected'];
            }
        }

        foreach ($wallets as $wallet) {
            $code = $wallet['currency_code'];
            if ($code) {
                $summary[$code] ??= [
                    'currency_code'               => $code,
                    'currency_symbol'             => $wallet['currency_symbol'],
                    'total_branch_confirmed'      => 0.0,
                    'total_branch_pending'        => 0.0,
                    'total_branch_projected'      => 0.0,
                    'total_wallet_confirmed'      => 0.0,
                    'total_wallet_pending'        => 0.0,
                    'total_wallet_projected'      => 0.0,
                    'total_system_confirmed'      => 0.0,
                    'total_system_pending'        => 0.0,
                    'total_system_projected'      => 0.0,
                ];
                $summary[$code]['total_wallet_confirmed'] += $wallet['balance_confirmed'];
                $summary[$code]['total_wallet_pending'] += $wallet['balance_pending'];
                $summary[$code]['total_wallet_projected'] += $wallet['balance_projected'];
            }
        }

        // Calculate system totals
        foreach ($summary as $code => &$totals) {
            $totals['total_system_confirmed'] = $totals['total_branch_confirmed'] + $totals['total_wallet_confirmed'];
            $totals['total_system_pending'] = $totals['total_branch_pending'] + $totals['total_wallet_pending'];
            $totals['total_system_projected'] = $totals['total_branch_projected'] + $totals['total_wallet_projected'];
        }

        return $this->sendData([
            'branches' => $branches,
            'wallets'  => $wallets,
            'summary'  => array_values($summary),
            'pending_transactions_count' => $pendingImpacts['transaction_count'],
        ]);
    }

    /**
     * Calculate the impact of pending transactions on balances
     * Returns an array with impacts grouped by entity type, entity ID, and currency
     */
    private function calculatePendingImpacts(): array
    {
        $impacts = [
            'branches' => [],
            'wallets' => [],
            'transaction_count' => 0,
        ];

        // Get all pending transactions with their types and currency
        $pendingTransactions = Transaction::with(['transactionType', 'currency'])
            ->where('status', TransactionStatus::PENDING->value)
            ->get();

        $impacts['transaction_count'] = $pendingTransactions->count();

        foreach ($pendingTransactions as $transaction) {
            $type = $transaction->transactionType;
            if (!$type) {
                continue;
            }

            $gross = (float) $transaction->gross_amount;
            $fee = (float) $transaction->fee_amount;
            $net = $gross - $fee;
            $currencyCode = $transaction->currency?->code;

            if (!$currencyCode) {
                continue;
            }

            // Helper to resolve amount based on config
            $resolve = fn(string $key) => match($key) {
                'fee' => $fee,
                'net' => $net,
                default => $gross,
            };

            // Branch (source) impact
            if (($type->branch_effect ?? 'none') !== 'none' && $transaction->branch_id) {
                $delta = $resolve($type->branch_amount ?? 'gross');
                $delta = $type->branch_effect === 'debit' ? -$delta : $delta;
                
                $impacts['branches'][$transaction->branch_id] ??= [];
                $impacts['branches'][$transaction->branch_id][$currencyCode] ??= 0.0;
                $impacts['branches'][$transaction->branch_id][$currencyCode] += $delta;
            }

            // Destination branch impact
            if (($type->dest_branch_effect ?? 'none') !== 'none' && $transaction->destination_branch_id) {
                $delta = $resolve($type->dest_branch_amount ?? 'gross');
                $delta = $type->dest_branch_effect === 'debit' ? -$delta : $delta;
                
                $impacts['branches'][$transaction->destination_branch_id] ??= [];
                $impacts['branches'][$transaction->destination_branch_id][$currencyCode] ??= 0.0;
                $impacts['branches'][$transaction->destination_branch_id][$currencyCode] += $delta;
            }

            // Wallet (source) impact
            if (($type->wallet_effect ?? 'none') !== 'none' && $transaction->wallet_id) {
                $delta = $resolve($type->wallet_amount ?? 'gross');
                $delta = $type->wallet_effect === 'debit' ? -$delta : $delta;
                
                $impacts['wallets'][$transaction->wallet_id] ??= [];
                $impacts['wallets'][$transaction->wallet_id][$currencyCode] ??= 0.0;
                $impacts['wallets'][$transaction->wallet_id][$currencyCode] += $delta;
            }

            // Destination wallet impact
            if (($type->dest_wallet_effect ?? 'none') !== 'none' && $transaction->dest_wallet_id) {
                $delta = $resolve($type->dest_wallet_amount ?? 'gross');
                $delta = $type->dest_wallet_effect === 'debit' ? -$delta : $delta;
                
                $impacts['wallets'][$transaction->dest_wallet_id] ??= [];
                $impacts['wallets'][$transaction->dest_wallet_id][$currencyCode] ??= 0.0;
                $impacts['wallets'][$transaction->dest_wallet_id][$currencyCode] += $delta;
            }
        }

        return $impacts;
    }
}
