<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Configs;

use App\Http\Requests\Api\OperatorRequest;
use App\Http\Resources\Api\OperatorResource;
use App\Services\Api\OperatorService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operator;

class OperatorController extends Controller
{
    public function __construct(private OperatorService $service){}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $operators = $this->service->getOperators($request);
        
        return $this->sendData($operators);//OperatorResource::collection($operators));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OperatorRequest $request)
    {
        $model = $this->service->create($request->validated());

        if ($request->hasFile('logo')) {
            $this->service->uploadLogo($model, $request->file('logo'));
        }

        return $this->sendResponse(new OperatorResource($model->load('country')), __('messages.operator_created_successfully'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Operator $operator)
    {
        $operator->load('country');
        return $this->sendData(new OperatorResource($operator));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OperatorRequest $request, Operator $operator)
    {
        $model = $this->service->update($operator, $request->validated());

        if ($request->hasFile('logo')) {
            $this->service->uploadLogo($model, $request->file('logo'));
        }

        return $this->sendResponse(new OperatorResource($model), __('messages.operator_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Operator $operator)
    {
        $this->service->destroy($operator);

        return $this->sendMessage(__('messages.operator_deleted_successfully'));
    }
}
