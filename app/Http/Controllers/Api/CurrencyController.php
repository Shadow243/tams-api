<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CurrencyRequest;
use App\Http\Resources\Api\CurrencyResource;
use App\Services\Api\CurrencyService;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrencyController extends Controller
{
    public function __construct(private CurrencyService $service){}
    /**
     * Display a paginated listing of currencies.
     */
    public function index(Request $request)
    {
        $currencies = $this->service->getCurrencies($request);

        return CurrencyResource::collection($currencies);
    }

    /**
     * Display a listing of all currencies without pagination (for dropdowns).
     */
    public function all(): JsonResponse
    {
        $currencies = $this->service->getAllCurrencies();

        return response()->json([
            'data' => CurrencyResource::collection($currencies),
        ]);
    }

    /**
     * Display a listing of active currencies without pagination (for dropdowns).
     */
    public function active(): JsonResponse
    {
        $currencies = $this->service->getActiveCurrencies();

        return response()->json([
            'data' => CurrencyResource::collection($currencies),
        ]);
    }

    /**
     * Get the default currency.
     */
    public function default(): JsonResponse
    {
        $currency = Currency::default()->first();

        if (!$currency) {
            return response()->json([
                'message' => __('currencies.no_default_currency'),
            ], 404);
        }

        return response()->json([
            'data' => $currency,
        ]);
    }

    /**
     * Store a newly created currency.
     */
    public function store(CurrencyRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $currency = $this->service->create($request->validated());
            $currency->load('country');

            DB::commit();

            return response()->json([
                'message' => __('currencies.created_successfully'),
                'data' => new CurrencyResource($currency),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => __('currencies.create_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified currency.
     */
    public function show(string $code): JsonResponse
    {
        $currency = Currency::with('country')->where('code', $code)->first();

        if (!$currency) {
            return response()->json([
                'message' => __('currencies.not_found'),
            ], 404);
        }

        return response()->json([
            'data' => $currency,
        ]);
    }

    /**
     * Update the specified currency.
     */
    public function update(CurrencyRequest $request, string $code): JsonResponse
    {
        try {
            $currency = Currency::where('code', $code)->first();

            if (!$currency) {
                return response()->json([
                    'message' => __('currencies.not_found'),
                ], 404);
            }

            DB::beginTransaction();

            $currency = $this->service->update($currency, $request->validated());

            DB::commit();

            return response()->json([
                'message' => __('currencies.updated_successfully'),
                'data' => new CurrencyResource($currency),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => __('currencies.update_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified currency.
     */
    public function destroy(string $code): JsonResponse
    {
        // Check permission
        if (!auth()->user()->can('supprimer_devises')) {
            return response()->json([
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        try {
            $currency = Currency::where('code', $code)->first();

            if (!$currency) {
                return response()->json([
                    'message' => __('currencies.not_found'),
                ], 404);
            }

            // Check if currency can be deleted
            $canDelete = $this->service->canBeDeleted($currency);
            
            if ($canDelete !== null) {
                return response()->json([
                    'message' => $canDelete['message'],
                ], $canDelete['code']);
            }

            $this->service->destroy($currency);

            return response()->json([
                'message' => __('currencies.deleted_successfully'),
            ]);

        } catch (\Exception $e) {            
            return response()->json([
                'message' => __('currencies.delete_error'),
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
