<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Configs;

use App\Http\Resources\Api\WalletResource;
use App\Http\Requests\Api\WalletRequest;
use App\Services\Api\WalletService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wallet;

/**
 * @group Configurations
 *
 * @subgroup Wallet
 */
class WalletController extends Controller
{
    public function __construct(private WalletService $service){}
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $wallets = $this->service->getWallets($request);

        return WalletResource::collection($wallets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WalletRequest $request)
    {
        $model = $this->service->create($request->validated());

        return $this->sendResponse(new WalletResource($model), __('messages.wallet_created_successfully'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Wallet $wallet)
    {
        $wallet->load(['branch', 'operator']);
        
        return $this->sendData(new WalletResource($wallet));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WalletRequest $request, Wallet $wallet)
    {
        $model = $this->service->update($wallet, $request->validated());

        return $this->sendResponse(new WalletResource($model), __('messages.wallet_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wallet $wallet)
    {
        $this->service->destroy($wallet);

        return $this->sendMessage(__('messages.wallet_deleted_successfully'));
    }

    /**
     * Toggle wallet status
     */
    public function toggleStatus(Wallet $wallet)
    {
        $model = $this->service->toggleStatus($wallet);

        return $this->sendResponse(new WalletResource($model), __('messages.wallet_status_updated_successfully'));
    }

    /**
     * Export wallets list to PDF.
     */
    public function exportPDF(Request $request)
    {
        return $this->service->exportToPDF($request);
    }
}
