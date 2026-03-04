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
}
