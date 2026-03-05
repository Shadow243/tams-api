<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Get all customers with optional filters
     */
    public function getCustomers(array $filters = []): LengthAwarePaginator
    {
        $query = Customer::query()
            ->withCount('transactions')
            ->orderBy('created_at', 'desc');

        // Search by phone, name, or national_id
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        // Filter by phone
        if (!empty($filters['phone'])) {
            $query->where('phone', $filters['phone']);
        }

        // Filter by national_id
        if (!empty($filters['national_id'])) {
            $query->where('national_id', $filters['national_id']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage);
    }

    /**
     * Find customer by phone
     */
    public function findByPhone(string $phone): ?Customer
    {
        return Customer::where('phone', $phone)
            ->withCount('transactions')
            ->first();
    }

    /**
     * Find customer by national ID
     */
    public function findByNationalId(string $nationalId): ?Customer
    {
        return Customer::where('national_id', $nationalId)
            ->withCount('transactions')
            ->first();
    }

    /**
     * Create a new customer
     */
    public function createCustomer(array $data): Customer
    {
        return Customer::create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'national_id' => $data['national_id'] ?? null,
        ]);
    }

    /**
     * Update customer
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer->update(array_filter([
            'full_name' => $data['full_name'] ?? $customer->full_name,
            'phone' => $data['phone'] ?? $customer->phone,
            'national_id' => $data['national_id'] ?? $customer->national_id,
        ]));

        return $customer->fresh();
    }

    /**
     * Delete customer
     */
    public function deleteCustomer(Customer $customer): bool
    {
        return $customer->delete();
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStatistics(Customer $customer): array
    {
        $transactions = $customer->transactions();

        return [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('net_amount'),
            'total_fees_paid' => $transactions->sum('fee_amount'),
            'by_status' => $transactions
                ->select('status', DB::raw('count(*) as count'), DB::raw('sum(net_amount) as total'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn ($item) => [
                    $item->status => [
                        'count' => $item->count,
                        'total' => $item->total,
                    ],
                ]),
            'by_type' => $transactions
                ->select('transaction_type_id', DB::raw('count(*) as count'), DB::raw('sum(net_amount) as total'))
                ->with('transactionType:id,name')
                ->groupBy('transaction_type_id')
                ->get()
                ->mapWithKeys(fn ($item) => [
                    $item->transaction_type?->name ?? 'Unknown' => [
                        'count' => $item->count,
                        'total' => $item->total,
                    ],
                ]),
            'last_transaction_at' => $transactions->latest()->first()?->created_at,
        ];
    }

    /**
     * Get top customers by transaction volume
     */
    public function getTopCustomers(int $limit = 10): Collection
    {
        return Customer::withCount('transactions')
            ->with(['transactions' => function ($query) {
                $query->selectRaw('customer_id, sum(net_amount) as total_amount')
                    ->groupBy('customer_id');
            }])
            ->orderBy('transactions_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
