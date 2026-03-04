<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\TransactionType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionTypeService
{
    /**
     * Get all transaction types with optional filters
     */
    public function getTransactionTypes(array $filters): LengthAwarePaginator
    {
        $query = TransactionType::query();

        // Filter by search (code, name, description)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by code
        if (!empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        // Order by name
        $query->orderBy('name', 'asc');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get all transaction types without pagination
     */
    public function getAllTransactionTypes()
    {
        return TransactionType::orderBy('name', 'asc')->get();
    }

    /**
     * Create a new transaction type
     */
    public function createTransactionType(array $data): TransactionType
    {
        return DB::transaction(function () use ($data) {
            return TransactionType::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);
        });
    }

    /**
     * Update an existing transaction type
     */
    public function updateTransactionType(TransactionType $transactionType, array $data): TransactionType
    {
        return DB::transaction(function () use ($transactionType, $data) {
            $transactionType->update([
                'code' => $data['code'] ?? $transactionType->code,
                'name' => $data['name'] ?? $transactionType->name,
                'description' => $data['description'] ?? $transactionType->description,
            ]);

            return $transactionType->fresh();
        });
    }

    /**
     * Delete a transaction type
     */
    public function deleteTransactionType(TransactionType $transactionType): bool
    {
        return $transactionType->delete();
    }

    /**
     * Get transaction type by code
     */
    public function getByCode(string $code): ?TransactionType
    {
        return TransactionType::where('code', $code)->first();
    }

    /**
     * Export transaction types to PDF
     */
    public function exportToPDF(array $filters): \Illuminate\Http\Response
    {
        $query = TransactionType::query();

        // Apply same filters as getTransactionTypes
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['code'])) {
            $query->where('code', $filters['code']);
        }

        $transactionTypes = $query->orderBy('name', 'asc')->get();

        $pdf = Pdf::loadView('pdf.transaction-types', [
            'transactionTypes' => $transactionTypes,
            'date' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('transaction-types-' . now()->format('Y-m-d') . '.pdf');
    }
}
