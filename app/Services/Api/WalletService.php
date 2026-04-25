<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\WalletStatus;
use App\Models\Wallet;
use Illuminate\Http\Request;

/**
 * Class WalletService
 * @package App\Services\Api
 */
final class WalletService
{
    /**
     * Store a newly created resource in storage.
     * @param array $data
     * @return Wallet
     */
    public function create(array $data): Wallet
    {
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = WalletStatus::ACTIVE;
        }

        // Set default balance if not provided
        if (!isset($data['balance'])) {
            $data['balance'] = 0;
        }

        // Set default currency_id if not provided (use default currency)
        if (!isset($data['currency_id'])) {
            $defaultCurrency = \App\Models\Currency::where('is_default', true)->first();
            if ($defaultCurrency) {
                $data['currency_id'] = $defaultCurrency->id;
            }
        }

        return Wallet::create($data);
    }

    /**
     * Update the specified resource in storage.
     * @param Wallet $wallet
     * @param array $data
     * @return Wallet
     */
    public function update(Wallet $wallet, array $data): Wallet
    {
        $wallet->update($data);
        return $wallet->fresh();
    }

    /**
     * Remove the specified resource from storage.
     * @param Wallet $wallet
     */
    public function destroy(Wallet $wallet): void
    {
        $wallet->delete();
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getWallets(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 100);
        $search = $request->input('search');
        $status = $request->input('status');
        $branchId = $request->input('branch_id');
        $operatorId = $request->input('operator_id');

        $query = Wallet::query()
            ->with(['branch', 'operator', 'currency'])
            ->select(['id', 'uuid', 'branch_id', 'operator_id', 'wallet_number', 'virtual_balance', 'currency_id', 'status', 'created_at', 'updated_at']);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('wallet_number', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhereHas('currency', function ($q) use ($search) {
                      $q->where('code', 'like', '%' . addcslashes($search, '%_\\') . '%')
                        ->orWhere('name', 'like', '%' . addcslashes($search, '%_\\') . '%');
                  });
            });
        }

        // Apply status filter
        if ($status && in_array($status, WalletStatus::values())) {
            $query->where('status', $status);
        }

        // Apply branch filter
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Apply operator filter
        if ($operatorId) {
            $query->where('operator_id', $operatorId);
        }

        return $query->orderBy('wallet_number')->paginate($perPage);
    }

    /**
     * Toggle wallet status between active and inactive
     * @param Wallet $wallet
     * @return Wallet
     */
    public function toggleStatus(Wallet $wallet): Wallet
    {
        $newStatus = $wallet->status === WalletStatus::ACTIVE 
            ? WalletStatus::INACTIVE 
            : WalletStatus::ACTIVE;

        $wallet->update(['status' => $newStatus]);
        
        return $wallet->fresh();
    }

    /**
     * Export wallets to PDF
     * @param Request $request
     * @return mixed
     */
    public function exportToPDF(Request $request)
    {
        $query = Wallet::query()->with(['branch', 'operator', 'currency']);

        // Apply same filters as getWallets method
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('wallet_number', 'like', "%{$search}%")
                    ->orWhereHas('currency', function ($q) use ($search) {
                        $q->where('code', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if (in_array($status, WalletStatus::values())) {
                $query->where('status', $status);
            }
        }

        if ($request->has('branch_id')) {
            $branchId = $request->input('branch_id');
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        }

        if ($request->has('operator_id')) {
            $operatorId = $request->input('operator_id');
            if ($operatorId) {
                $query->where('operator_id', $operatorId);
            }
        }

        // Get all matching wallets (no pagination for export)
        $wallets = $query->orderBy('wallet_number')->get();

        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.wallets', [
            'wallets' => $wallets,
            'date' => now()->format('d/m/Y H:i'),
            'total' => $wallets->count(),
        ]);

        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');

        // Return PDF download
        return $pdf->download('wallets_' . now()->format('Y-m-d') . '.pdf');
    }
}
