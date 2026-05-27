<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerRequest;
use App\Http\Resources\Api\CustomerResource;
use App\Models\Customer;
use App\Services\Api\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    /**
     * Display a listing of customers
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'phone', 'national_id', 'per_page']);
        $customers = $this->customerService->getCustomers($filters);

        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created customer
     */
    public function store(CustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return response()->json([
            'message' => __('messages.customer_created_successfully'),
            'data' => new CustomerResource($customer),
        ], 201);
    }

    /**
     * Display the specified customer
     */
    public function show(Customer $customer): CustomerResource
    {
        $customer->loadCount('transactions');
        return new CustomerResource($customer);
    }

    /**
     * Update the specified customer
     */
    public function update(CustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($customer, $request->validated());

        return response()->json([
            'message' => __('messages.customer_updated_successfully'),
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Remove the specified customer
     */
    public function destroy(Customer $customer): JsonResponse
    {
        // Check if customer has transactions
        if ($customer->transactions()->exists()) {
            return response()->json([
                'message' => __('messages.customer_has_transactions'),
            ], 422);
        }

        $this->customerService->deleteCustomer($customer);

        return response()->json([
            'message' => __('messages.customer_deleted_successfully'),
        ]);
    }

    /**
     * Get customer statistics
     */
    public function statistics(Customer $customer): JsonResponse
    {
        $statistics = $this->customerService->getCustomerStatistics($customer);

        return response()->json([
            'data' => $statistics,
        ]);
    }

    /**
     * Find customer by phone
     */
    public function findByPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^[0-9]{7,15}$/',
        ]);

        $customer = $this->customerService->findByPhone($request->phone);

        if (!$customer) {
            return response()->json([
                'message' => __('messages.customer_not_found'),
            ], 404);
        }

        return response()->json([
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Get top customers
     */
    public function topCustomers(Request $request): AnonymousResourceCollection
    {
        $limit = (int) ($request->get('limit', 10));
        $customers = $this->customerService->getTopCustomers($limit);

        return CustomerResource::collection($customers);
    }
}
