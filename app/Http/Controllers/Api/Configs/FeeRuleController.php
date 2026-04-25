<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Configs;

use App\Http\Resources\Api\FeeRuleResource;
use App\Http\Requests\Api\FeeRuleRequest;
use App\Services\Api\FeeRuleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FeeRule;

/**
 * @group Configurations
 *
 * @subgroup FeeRule
 */
class FeeRuleController extends Controller
{
    public function __construct(private FeeRuleService $service){}
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $feeRules = $this->service->getFeeRules($request);

        return FeeRuleResource::collection($feeRules);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FeeRuleRequest $request)
    {
        $model = $this->service->create($request->validated());

        // Load relationships
        $model->load(['transactionType', 'operator', 'branch']);

        return $this->sendResponse(
            new FeeRuleResource($model), 
            __('messages.fee_rule_created_successfully'), 
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(FeeRule $feeRule)
    {
        $feeRule->load(['transactionType', 'operator', 'branch']);
        
        return $this->sendData(new FeeRuleResource($feeRule));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FeeRuleRequest $request, FeeRule $feeRule)
    {
        $model = $this->service->update($feeRule, $request->validated());

        // Load relationships
        $model->load(['transactionType', 'operator', 'branch']);

        return $this->sendResponse(
            new FeeRuleResource($model), 
            __('messages.fee_rule_updated_successfully')
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeeRule $feeRule)
    {
        $this->service->destroy($feeRule);

        return $this->sendMessage(__('messages.fee_rule_deleted_successfully'));
    }

    /**
     * Toggle fee rule status
     */
    public function toggleStatus(FeeRule $feeRule)
    {
        $model = $this->service->toggleStatus($feeRule);

        // Load relationships
        $model->load(['transactionType', 'operator', 'branch']);

        return $this->sendResponse(
            new FeeRuleResource($model), 
            __('messages.fee_rule_status_updated_successfully')
        );
    }

    /**
     * Get applicable fee rule for a transaction
     */
    public function getApplicable(Request $request)
    {
        $request->validate([
            'transaction_type_id' => 'required|integer|exists:transaction_types,id',
            'operator_id' => 'nullable|integer|exists:operators,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $feeRule = $this->service->getApplicableFeeRule(
            $request->integer('transaction_type_id'),
            $request->integer('operator_id'),
            $request->integer('branch_id')
        );

        if (!$feeRule) {
            return $this->sendErrorResponse(__('messages.no_applicable_fee_rule_found'), 404);
        }

        $feeRule->load(['transactionType', 'operator', 'branch']);

        // Calculate fee if amount is provided
        $calculatedFee = null;
        if ($request->has('amount')) {
            $amount = (float) $request->input('amount');
            $calculatedFee = $this->service->calculateFee($feeRule, $amount);
        }

        $data = new FeeRuleResource($feeRule);
        $response = $data->toArray($request);
        
        if ($calculatedFee !== null) {
            $response['calculated_fee'] = $calculatedFee;
        }

        return $this->sendData($response);
    }

    /**
     * Export fee rules list to PDF.
     */
    public function exportPDF(Request $request)
    {
        return $this->service->exportToPDF($request);
    }
}
