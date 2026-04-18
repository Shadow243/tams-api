<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Configs;

use App\Http\Resources\Api\BranchResource;
use App\Http\Requests\Api\BranchRequest;
use App\Services\Api\BranchService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;

/**
 * @group Configurations
 *
 * @subgroup Branch
 */
class BranchController extends Controller
{
    public function __construct(private BranchService $service){}
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $branches = $this->service->getBranches($request);

        return BranchResource::collection($branches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BranchRequest $request)
    {
        $model = $this->service->create($request->validated());

        return $this->sendResponse(new BranchResource($model), __('messages.branch_created_successfully'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
        $branch->load('country');
        
        return $this->sendData(new BranchResource($branch));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        $model = $this->service->update($branch, $request->validated());

        return $this->sendResponse(new BranchResource($model), __('messages.branch_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        $this->service->destroy($branch);

        return $this->sendMessage(__('messages.branch_deleted_successfully'));
    }

    /**
     * Toggle branch status
     */
    public function toggleStatus(Branch $branch)
    {
        $model = $this->service->toggleStatus($branch);

        return $this->sendResponse(new BranchResource($model), __('messages.branch_status_updated_successfully'));
    }

    /**
     * Export branches list to PDF.
     */
    public function exportPDF(Request $request)
    {
        return $this->service->exportToPDF($request);
    }

    /**
     * Update or set branch balances for specific currencies
     * 
     * @param Request $request
     * @param Branch $branch
     * @return \Illuminate\Http\JsonResponse
     * 
     * Expected request format:
     * {
     *   "balances": [
     *     {"currency_code": "USD", "amount": 10000},
     *     {"currency_code": "EUR", "amount": 5000},
     *     {"currency_code": "CDF", "amount": 2000000}
     *   ]
     * }
     */
    public function updateBalances(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'balances' => 'required|array|min:1',
            'balances.*.currency_code' => 'required|string|exists:currencies,code',
            'balances.*.amount' => 'required|numeric|min:0',
        ]);

        foreach ($validated['balances'] as $balanceData) {
            $branchBalance = $branch->getOrCreateBalance($balanceData['currency_code']);
            $branchBalance->update([
                'cash_balance' => $balanceData['amount']
            ]);
        }

        // Reload branch with balances
        $branch->load(['balances.currency', 'country']);

        return $this->sendResponse(
            new BranchResource($branch), 
            __('messages.branch_balances_updated_successfully')
        );
    }

    /**
     * Get branch balances for all currencies
     * 
     * @param Branch $branch
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalances(Branch $branch)
    {
        $branch->load(['balances.currency']);

        $balances = $branch->balances->map(function ($balance) {
            return [
                'currency_code' => $balance->currency_code,
                'currency_name' => $balance->currency->name ?? null,
                'currency_symbol' => $balance->currency->symbol ?? null,
                'cash_balance' => (float) $balance->cash_balance,
                'formatted_balance' => number_format((float) $balance->cash_balance, 2),
            ];
        });

        return $this->sendData([
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'balances' => $balances,
        ]);
    }
}
