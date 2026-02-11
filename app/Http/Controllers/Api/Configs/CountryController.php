<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Configs;

use App\Http\Resources\Api\CountryResource;
use App\Http\Requests\Api\CountryRequest;
use App\Services\Api\CountryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;

/**
 * @group Configurations
 *
 * @subgroup Country
 */
class CountryController extends Controller
{
    public function __construct(private CountryService $service){}
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $countries = $this->service->getCountries($request);

        return CountryResource::collection($countries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CountryRequest $request)
    {
        $model = $this->service->create($request->validated());

        return $this->sendResponse(new CountryResource($model), __('messages.country_created_successfully'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        return $this->sendData(new CountryResource($country));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CountryRequest $request, Country $country)
    {
        $model = $this->service->update($country, $request->validated());

        return $this->sendResponse(new CountryResource($model), __('messages.country_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $this->service->destroy($country);

        return $this->sendMessage(__('messages.country_deleted_successfully'));
    }
}
