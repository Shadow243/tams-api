<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Configs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransactionTypeRequest;
use App\Http\Resources\Api\TransactionTypeResource;
use App\Models\TransactionType;
use App\Services\Api\TransactionTypeService;
use Illuminate\Http\Request;

class TransactionTypeController extends Controller
{
    public function __construct(
        protected TransactionTypeService $transactionTypeService
    ) {}

    /**
     * Display a listing of the transaction types.
     */
    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'code' => $request->input('code'),
            'per_page' => $request->input('per_page', 15),
        ];

        $transactionTypes = $this->transactionTypeService->getTransactionTypes($filters);

        return TransactionTypeResource::collection($transactionTypes);
    }

    /**
     * Store a newly created transaction type.
     */
    public function store(TransactionTypeRequest $request)
    {
        $transactionType = $this->transactionTypeService->createTransactionType($request->validated());

        return $this->sendResponse(
            new TransactionTypeResource($transactionType),
            __('messages.transaction_type_created_successfully'),
            201
        );
    }

    /**
     * Display the specified transaction type.
     */
    public function show(TransactionType $transactionType)
    {
        return $this->sendData(new TransactionTypeResource($transactionType));
    }

    /**
     * Update the specified transaction type.
     */
    public function update(TransactionTypeRequest $request, TransactionType $transactionType)
    {
        $transactionType = $this->transactionTypeService->updateTransactionType(
            $transactionType,
            $request->validated()
        );

        return $this->sendResponse(
            new TransactionTypeResource($transactionType),
            __('messages.transaction_type_updated_successfully')
        );
    }

    /**
     * Remove the specified transaction type.
     */
    public function destroy(TransactionType $transactionType)
    {
        $this->transactionTypeService->deleteTransactionType($transactionType);

        return $this->sendMessage(__('messages.transaction_type_deleted_successfully'));
    }

    /**
     * Export transaction types to PDF
     */
    public function exportPDF(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'code' => $request->input('code'),
        ];

        return $this->transactionTypeService->exportToPDF($filters);
    }
}
