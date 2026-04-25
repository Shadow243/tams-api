<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Wallet;

class BalanceReportController extends Controller
{
    public function index()
    {
        $branches = Branch::with(['balances.currency'])
            ->orderBy('name')
            ->get()
            ->map(fn($branch) => [
                'id'       => $branch->id,
                'name'     => $branch->name,
                'code'     => $branch->code,
                'status'   => $branch->status->value,
                'balances' => $branch->balances->map(fn($b) => [
                    'currency_code'   => $b->currency_code,
                    'currency_name'   => $b->currency?->name,
                    'currency_symbol' => $b->currency?->symbol,
                    'cash_balance'    => (float) $b->cash_balance,
                ])->values(),
            ]);

        $wallets = Wallet::with(['branch:id,name', 'operator:id,name', 'currency:id,code,symbol,name'])
            ->orderBy('wallet_number')
            ->get()
            ->map(fn($wallet) => [
                'id'              => $wallet->id,
                'wallet_number'   => $wallet->wallet_number,
                'operator_name'   => $wallet->operator?->name,
                'branch_name'     => $wallet->branch?->name,
                'currency_code'   => $wallet->currency?->code,
                'currency_symbol' => $wallet->currency?->symbol,
                'virtual_balance' => (float) $wallet->virtual_balance,
                'status'          => $wallet->status->value,
            ]);

        // Aggregate totals by currency
        $summary = [];

        foreach ($branches as $branch) {
            foreach ($branch['balances'] as $bal) {
                $code = $bal['currency_code'];
                $summary[$code] ??= [
                    'currency_code'        => $code,
                    'currency_symbol'      => $bal['currency_symbol'],
                    'total_branch_cash'    => 0.0,
                    'total_wallet_virtual' => 0.0,
                ];
                $summary[$code]['total_branch_cash'] += $bal['cash_balance'];
            }
        }

        foreach ($wallets as $wallet) {
            $code = $wallet['currency_code'];
            if ($code) {
                $summary[$code] ??= [
                    'currency_code'        => $code,
                    'currency_symbol'      => $wallet['currency_symbol'],
                    'total_branch_cash'    => 0.0,
                    'total_wallet_virtual' => 0.0,
                ];
                $summary[$code]['total_wallet_virtual'] += $wallet['virtual_balance'];
            }
        }

        return $this->sendData([
            'branches' => $branches,
            'wallets'  => $wallets,
            'summary'  => array_values($summary),
        ]);
    }
}
