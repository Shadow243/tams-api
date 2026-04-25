<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Currency;
use Illuminate\Http\Request;

/**
 * Class CurrencyService
 * @package App\Services\Api
 */
final class CurrencyService
{
    /**
     * Display a listing of all currencies with pagination and search.
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCurrencies(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $search = $request->input('search');
        $isActive = $request->input('is_active');
        $countryId = $request->input('country_id');

        $query = Currency::query()
            ->with(['country'])
            ->select(['id', 'code', 'name', 'symbol', 'country_id', 'decimal_places', 'exchange_rate', 'is_active', 'is_default', 'created_at', 'updated_at']);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('name', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhere('symbol', 'like', '%' . addcslashes($search, '%_\\') . '%')
                  ->orWhereHas('country', function ($q) use ($search) {
                      $q->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%');
                  });
            });
        }

        // Apply active status filter
        if ($isActive !== null) {
            $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
        }

        // Apply country filter
        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('code')
            ->paginate($perPage);
    }

    /**
     * Get all active currencies without pagination.
     */
    public function getActiveCurrencies()
    {
        return Currency::with('country')
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('code')
            ->get();
    }

    /**
     * Get all currencies without pagination.
     */
    public function getAllCurrencies()
    {
        return Currency::with('country')
            ->orderBy('is_default', 'desc')
            ->orderBy('code')
            ->get();
    }

    /**
     * Store a newly created currency.
     * @param array $data
     * @return Currency
     */
    public function create(array $data): Currency
    {
        // If this currency is set as default, unset other defaults
        if (!empty($data['is_default']) && $data['is_default']) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        return Currency::create($data);
    }

    /**
     * Update the specified currency.
     * @param Currency $currency
     * @param array $data
     * @return Currency
     */
    public function update(Currency $currency, array $data): Currency
    {
        // If this currency is set as default, unset other defaults
        if (!empty($data['is_default']) && $data['is_default']) {
            Currency::where('id', '!=', $currency->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $currency->update($data);
        return $currency->fresh(['country']);
    }

    /**
     * Check if a currency can be deleted.
     * @param Currency $currency
     * @return array|null Returns error details if currency cannot be deleted, null if it can be deleted
     */
    public function canBeDeleted(Currency $currency): ?array
    {
        // Prevent deletion of default currency
        if ($currency->is_default) {
            return [
                'message' => __('currencies.cannot_delete_default'),
                'code' => 422,
            ];
        }

        // Prevent deletion if currency is in use by wallets
        try {
            if ($currency->wallets()->exists()) {
                return [
                    'message' => __('currencies.used_by_wallets'),
                    'code' => 422,
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Could not check currency usage in wallets: ' . $e->getMessage());
        }

        // Prevent deletion if currency is in use by transactions
        try {
            if ($currency->transactions()->exists()) {
                return [
                    'message' => __('currencies.in_use'),
                    'code' => 422,
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Could not check currency usage in transactions: ' . $e->getMessage());
        }

        // Prevent deletion if any branch has a non-zero cash balance in this currency
        if ($currency->branchBalances()->where('cash_balance', '>', 0)->exists()) {
            return [
                'message' => __('currencies.used_by_branch_balances'),
                'code' => 422,
            ];
        }

        return null;
    }

    /**
     * Remove the specified currency.
     * @param Currency $currency
     */
    public function destroy(Currency $currency): void
    {
        // Remove zero-balance branch_balances first to satisfy the FK constraint
        $currency->branchBalances()->where('cash_balance', 0)->delete();

        $currency->delete();
    }
}
