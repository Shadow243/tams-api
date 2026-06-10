<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TransactionStatus;
use App\Http\Resources\Api\TransactionResource;
use App\Http\Requests\Api\TransactionRequest;
use App\Services\Api\TransactionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;

/**
 * @group Transactions
 */
class TransactionController extends Controller
{
    public function __construct(private TransactionService $service){}
    
    /**
     * Find transaction by ID or UUID
     */
    private function findTransaction(string $identifier): ?Transaction
    {
        return Transaction::where('uuid', $identifier)->orWhere('id', $identifier)->first();
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = $this->service->getTransactions($request);

        return TransactionResource::collection($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransactionRequest $request)
    {
        try {
            $model = $this->service->create($request->validated());
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), 422);
        }

        // Load relationships
        $model->load([
            'transactionType',
            'branch',
            'destinationBranch',
            'user',
            'customer',
            'destCustomer',
            'wallet',
            'destWallet',
            'feeRule',
            'currency',
        ]);

        return $this->sendResponse(
            new TransactionResource($model),
            __('messages.transaction_created_successfully'),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $transaction)
    {
        // Accept both UUID and ID
        $transaction = $this->findTransaction($transaction);
        
        if (!$transaction) {
            return $this->sendErrorResponse(
                __('messages.transaction_not_found'),
                404
            );
        }

        $transaction->load([
            'transactionType',
            'branch',
            'destinationBranch',
            'user',
            'customer',
            'destCustomer',
            'wallet',
            'destWallet',
            'feeRule',
            'currency',
            'parentTransaction',
            'childTransactions',
        ]);
        
        return $this->sendData(new TransactionResource($transaction));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransactionRequest $request, string $transaction)
    {
        // Accept both UUID and ID
        $transaction = $this->findTransaction($transaction);
        
        if (!$transaction) {
            return $this->sendErrorResponse(
                __('messages.transaction_cannot_be_modified'),
                403
            );
        }

        try {
            $model = $this->service->update($transaction, $request->validated());
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), 422);
        }

        // Load relationships
        $model->load([
            'transactionType',
            'branch',
            'destinationBranch',
            'user',
            'customer',
            'destCustomer',
            'wallet',
            'destWallet',
            'feeRule',
            'currency',
        ]);

        return $this->sendResponse(
            new TransactionResource($model),
            __('messages.transaction_updated_successfully')
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $transaction)
    {
        // Accept both UUID and ID
        $transaction = $this->findTransaction($transaction);
        
        if (!$transaction->canBeCancelled()) {
            return $this->sendErrorResponse(
                __('messages.transaction_cannot_be_deleted'),
                403
            );
        }

        if ($transaction->destination_branch_id && auth()->user()->hasRole('agent')) {
            return $this->sendErrorResponse(
                __('messages.transaction_cross_branch_supervisor_only'),
                403
            );
        }

        $this->service->destroy($transaction);

        return $this->sendResponse(
            null, 
            __('messages.transaction_deleted_successfully')
        );
    }

    /**
     * Cancel a transaction
     */
    public function cancel(Request $request, string $transaction)
    {
        // Accept both UUID and ID
        $transaction = $this->findTransaction($transaction);

        $request->validate(['description' => ['nullable', 'string', 'max:1000']]);

        try {
            $model = $this->service->cancel($transaction);

            if ($request->filled('description')) {
                $model->description = $request->input('description');
                $model->save();
            }

            $model->load(['transactionType', 'branch', 'destinationBranch', 'user', 'customer', 'destCustomer', 'wallet', 'destWallet', 'currency']);

            return $this->sendResponse(
                new TransactionResource($model),
                __('messages.transaction_cancelled_successfully')
            );
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Complete a transaction
     */
    public function complete(Request $request, string $transaction)
    {
        // Accept both UUID and ID
        $transaction = $this->findTransaction($transaction);

        $request->validate(['description' => ['nullable', 'string', 'max:1000']]);

        try {
            $model = $this->service->complete($transaction);
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), 422);
        }

        if ($request->filled('description')) {
            $model->description = $request->input('description');
            $model->save();
        }
        
        // Load all relationships needed for receipt
        $model->load([
            'transactionType',
            'branch',
            'destinationBranch',
            'user',
            'completedBy',
            'servedByBranch',
            'customer',
            'destCustomer',
            'wallet',
            'destWallet',
            'feeRule',
            'currency',
        ]);

        // Prepare receipt data
        $receiptData = $this->prepareReceiptData($model);

        return $this->sendResponse(
            [
                'transaction' => new TransactionResource($model),
                'receipt' => $receiptData,
            ],
            __('messages.transaction_completed_successfully')
        );
    }

    /**
     * Prepare receipt data for a transaction
     * @param Transaction $transaction
     * @return array
     */
    private function prepareReceiptData(Transaction $transaction): array
    {
        $logoPath = public_path('images/logo.png');
        $logoSrc  = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        return [
            'reference' => $transaction->reference,
            'transaction_type' => $transaction->transactionType?->name,
            'customer_name' => $transaction->customer?->full_name,
            'customer_phone' => $transaction->customer_phone,
            'dest_customer_name' => $transaction->destCustomer?->full_name,
            'dest_customer_phone' => $transaction->destCustomer?->phone,
            'branch_name' => $transaction->branch?->name,
            'destination_branch_name' => $transaction->destinationBranch?->name,
            'wallet_number' => $transaction->wallet?->wallet_number,
            'dest_wallet_number' => $transaction->destWallet?->wallet_number,
            'gross_amount' => $transaction->gross_amount,
            'fee_amount' => $transaction->fee_amount,
            'net_amount' => $transaction->net_amount,
            'currency_code' => $transaction->currency?->code ?? $transaction->currency_code,
            'currency_symbol' => $transaction->currency?->symbol,
            'withdrawal_code' => $transaction->withdrawal_code,
            'status' => $transaction->status->value,
            'created_at' => $transaction->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $transaction->user?->name,
            'completed_at' => $transaction->completed_at?->format('Y-m-d H:i:s'),
            'completed_by' => $transaction->completedBy?->name,
            'served_by_branch' => $transaction->servedByBranch?->name,
            'description' => $transaction->description,
            'logo' => $logoSrc,
        ];
    }

    /**
     * Change transaction status
     */
    public function changeStatus(Request $request, string $transaction)
    {
        // Accept both UUID and ID
        $transaction = $this->findTransaction($transaction);

        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_map(fn($case) => $case->value, TransactionStatus::cases()))]
        ]);

        if (TransactionStatus::from($request->status) === TransactionStatus::COMPLETED) {
            return $this->sendErrorResponse(
                __('messages.use_complete_endpoint'),
                422
            );
        }

        $status = TransactionStatus::from($request->status);
        $model = $this->service->changeStatus($transaction, $status);
        $model->load(['transactionType', 'branch', 'user', 'customer']);

        return $this->sendResponse(
            new TransactionResource($model),
            __('messages.transaction_status_updated_successfully')
        );
    }

    /**
     * Verify withdrawal code
     */
    public function verifyWithdrawalCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6']
        ]);

        $transaction = $this->service->verifyWithdrawalCode($request->code);

        if (!$transaction) {
            return $this->sendErrorResponse(
                __('messages.invalid_withdrawal_code'),
                404
            );
        }

        $transaction->load(['transactionType', 'branch', 'user', 'customer']);

        return $this->sendData(new TransactionResource($transaction));
    }

    /**
     * Get transaction statistics
     */
    public function statistics(Request $request)
    {
        $statistics = $this->service->getStatistics($request);

        return $this->sendData($statistics);
    }

    /**
     * Get dashboard statistics
     */
    public function dashboardStatistics(Request $request)
    {
        $statistics = $this->service->getDashboardStatistics($request);

        return $this->sendData($statistics);
    }

    /**
     * Generate receipt for a transaction
     */
    public function receipt(string $transaction)
    {
        // Accept both UUID and ID
        $transaction = $this->findTransaction($transaction);
        
        // Load all necessary relationships
        $transaction->load([
            'transactionType',
            'branch',
            'destinationBranch',
            'user',
            'customer',
            'destCustomer',
            'wallet',
            'destWallet',
            'feeRule',
            'currency',
        ]);

        $pdf = app('dompdf.wrapper');

        $logoPath = public_path('images/logo.png');
        $logoSrc  = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf->loadView('pdf.transaction-receipt', [
            'transaction' => $transaction,
            'logoSrc'     => $logoSrc,
        ])->setPaper([0, 0, 226, 566], 'portrait'); // 80mm x 200mm thermal paper

        return $pdf->download('receipt-' . $transaction->reference . '.pdf');
    }

    /**
     * Export transactions to PDF
     */
    public function exportPDF(Request $request)
    {
        // Fetch ALL matching transactions (no pagination) so totals are accurate
        $transactions = $this->service->getAllTransactions($request);

        // Group by currency so totals are never mixed across currencies.
        // We use currency_id (the FK) as the group key — authoritative and never stale.
        // For display we resolve: currency relation > currency_code column > 'N/A'.
        $groups = $transactions
            ->groupBy(fn ($t) => $t->currency_id ?? ('code:' . ($t->currency_code ?: 'N/A')))
            ->map(fn ($g) => [
                'currency_code' => $g->first()->currency?->code
                                ?? $g->first()->currency_code
                                ?: 'N/A',
                'currency_name' => $g->first()->currency?->name ?? null,
                'transactions'  => $g->values(),
                'gross'         => $g->sum('gross_amount'),
                'fee'           => $g->sum('fee_amount'),
                'net'           => $g->sum('net_amount'),
                'count'         => $g->count(),
            ])
            ->values();

        // Embed logo as base64 so dompdf can render it without HTTP requests
        $logoPath = public_path('images/logo.png');
        $logoSrc  = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('A4', 'landscape');
        $pdf->loadView('pdf.transactions', [
            'groups'     => $groups,
            'totalCount' => $transactions->count(),
            'filters'    => $request->all(),
            'logoSrc'    => $logoSrc,
        ]);

        return $pdf->download('transactions-' . date('Y-m-d-H-i-s') . '.pdf');
    }
}
