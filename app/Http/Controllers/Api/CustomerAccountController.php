<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CustomerAccountResource;
use App\Http\Resources\Api\AccountTransactionResource;
use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\CustomerAccount;
use App\Models\Customer;
use App\Services\CustomerAccountService;
use Illuminate\Support\Facades\DB;
use App\Services\InterestCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @group Customer Accounts
 */
class CustomerAccountController extends Controller
{
    public function __construct(
        private CustomerAccountService $accountService,
        private InterestCalculationService $interestService
    ) {}

    /**
     * Display a listing of customer accounts.
     */
    public function index(Request $request)
    {
        $query = CustomerAccount::with(['customer', 'currency', 'branch', 'interestSettings']);

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by VIP
        if ($request->filled('is_vip')) {
            $query->where('is_vip', $request->boolean('is_vip'));
        }

        // Filter accounts in debt
        if ($request->boolean('in_debt')) {
            $query->inDebt();
        }

        // Search by account number or customer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Sort: always group by customer first, then by the requested field
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy('customer_id', 'asc')->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $accounts = $query->paginate($perPage);

        return CustomerAccountResource::collection($accounts);
    }

    /**
     * Store a newly created customer account.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'currency_id' => 'required|exists:currencies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_vip' => 'boolean',
            'notes' => 'nullable|string',
            'initial_deposit' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse($validator->errors()->first(), 422);
        }

        $data = $validator->validated();
        $initialDeposit = $data['initial_deposit'] ?? 0;
        unset($data['initial_deposit']);
        $data['status'] = $data['status'] ?? 'active';

        $account = CustomerAccount::create($data);

        // Make initial deposit if provided
        if ($initialDeposit > 0) {
            $this->accountService->deposit(
                $account,
                $initialDeposit,
                $request->user(),
                'Dépôt initial à l\'ouverture du compte'
            );
            $account->refresh();
        }

        $account->refresh()->load(['customer', 'currency', 'branch']);

        return $this->sendResponse(
            new CustomerAccountResource($account),
            'Compte client créé avec succès',
            201
        );
    }

    /**
     * Display the specified customer account.
     */
    public function show(string $identifier)
    {
        $account = CustomerAccount::where('uuid', $identifier)
            ->orWhere('account_number', $identifier)
            ->orWhere('id', $identifier)
            ->with(['customer', 'currency', 'branch', 'interestSettings'])
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        return $this->sendResponse(new CustomerAccountResource($account));
    }

    /**
     * Update the specified customer account.
     */
    public function update(Request $request, string $identifier)
    {
        $account = CustomerAccount::where('uuid', $identifier)
            ->orWhere('account_number', $identifier)
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        $validator = Validator::make($request->all(), [
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => ['nullable', Rule::in(['active', 'suspended', 'closed'])],
            'is_vip' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse($validator->errors()->first(), 422);
        }

        $account->update($validator->validated());
        $account->load(['customer', 'currency', 'branch', 'interestSettings']);

        return $this->sendResponse(
            new CustomerAccountResource($account),
            'Compte client mis à jour avec succès'
        );
    }

    /**
     * Get account statement/transactions.
     */
    public function transactions(Request $request, string $identifier)
    {
        $account = CustomerAccount::where('uuid', $identifier)
            ->orWhere('account_number', $identifier)
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        $from = $request->filled('from') ? \Carbon\Carbon::parse($request->from) : null;
        $to = $request->filled('to') ? \Carbon\Carbon::parse($request->to) : null;

        $transactions = $this->accountService->getStatement($account, $from, $to);

        return AccountTransactionResource::collection($transactions);
    }

    /**
     * Make a deposit to the account.
     */
    public function deposit(Request $request, string $identifier)
    {
        $account = CustomerAccount::where('uuid', $identifier)
            ->orWhere('account_number', $identifier)
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse($validator->errors()->first(), 422);
        }

        $branchId = $request->input('branch_id') ?? $request->user()->branch_id;

        try {
            $transaction = $this->accountService->deposit(
                $account,
                (float) $request->amount,
                $request->user(),
                $request->description,
                null,
                $branchId ? (int) $branchId : null
            );

            $transaction->load(['customerAccount.customer', 'user', 'branch']);

            return $this->sendResponse(
                new AccountTransactionResource($transaction),
                'Dépôt effectué avec succès'
            );
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Make a withdrawal from the account.
     */
    public function withdraw(Request $request, string $identifier)
    {
        $account = CustomerAccount::where('uuid', $identifier)
            ->orWhere('account_number', $identifier)
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse($validator->errors()->first(), 422);
        }

        $branchId = $request->input('branch_id') ?? $request->user()->branch_id;

        try {
            $transaction = $this->accountService->withdraw(
                $account,
                (float) $request->amount,
                $request->user(),
                $request->description,
                null,
                $branchId ? (int) $branchId : null
            );

            $transaction->load(['customerAccount.customer', 'user', 'branch']);

            return $this->sendResponse(
                new AccountTransactionResource($transaction),
                'Retrait effectué avec succès'
            );
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Apply interest to the account.
     */
    public function applyInterest(Request $request, string $identifier)
    {
        $account = CustomerAccount::where('uuid', $identifier)
            ->orWhere('account_number', $identifier)
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        try {
            $transaction = $this->accountService->applyInterest($account, $request->user());

            if (!$transaction) {
                return $this->sendErrorResponse('Aucun intérêt à appliquer pour ce compte', 400);
            }

            $transaction->load(['customerAccount.customer', 'user']);

            return $this->sendResponse(
                new AccountTransactionResource($transaction),
                'Intérêts appliqués avec succès'
            );
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Simulate interest calculation.
     */
    public function simulateInterest(string $identifier)
    {
        $account = CustomerAccount::where('uuid', $identifier)
            ->orWhere('account_number', $identifier)
            ->with('interestSettings')
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        $simulation = $this->interestService->simulateInterest($account);

        return $this->sendData($simulation);
    }

    /**
     * Make a balance adjustment.
     */
    public function adjust(Request $request, string $identifier)
    {
        $account = CustomerAccount::where('uuid', $identifier)
            ->orWhere('account_number', $identifier)
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse($validator->errors()->first(), 422);
        }

        try {
            $transaction = $this->accountService->adjust(
                $account,
                $request->amount,
                $request->user(),
                $request->description
            );

            $transaction->load(['customerAccount.customer', 'user']);

            return $this->sendResponse(
                new AccountTransactionResource($transaction),
                'Ajustement effectué avec succès'
            );
        } catch (\Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Dashboard report for customer accounts.
     */
    public function dashboardReport(Request $request)
    {
        $branchId   = $request->input('branch_id');
        $startDate  = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate    = $request->input('end_date', now()->toDateString());

        // ── Global account stats ─────────────────────────────────────────
        $accountsQuery = CustomerAccount::query();
        if ($branchId) $accountsQuery->where('branch_id', $branchId);

        $totalAccounts   = (clone $accountsQuery)->count();
        $activeAccounts  = (clone $accountsQuery)->where('status', 'active')->count();
        $vipAccounts     = (clone $accountsQuery)->where('is_vip', true)->count();
        $inDebtAccounts  = (clone $accountsQuery)->where('balance', '<', 0)->count();
        $totalBalance    = (clone $accountsQuery)->sum('balance');
        $totalDebt       = (clone $accountsQuery)->where('balance', '<', 0)->sum(DB::raw('ABS(balance)'));
        $totalCredit     = (clone $accountsQuery)->sum('credit_limit');

        // ── Transaction stats for the period ────────────────────────────
        $txQuery = AccountTransaction::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        if ($branchId) $txQuery->where('branch_id', $branchId);

        $totalDeposits    = (clone $txQuery)->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = (clone $txQuery)->where('type', 'withdrawal')->sum('amount');
        $countDeposits    = (clone $txQuery)->where('type', 'deposit')->count();
        $countWithdrawals = (clone $txQuery)->where('type', 'withdrawal')->count();
        $totalInterest    = (clone $txQuery)->whereIn('type', ['interest_credit', 'interest_debit'])->sum('amount');

        // ── Operations by branch ─────────────────────────────────────────
        $byBranch = AccountTransaction::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select(
                'branch_id',
                DB::raw("SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END) as total_deposits"),
                DB::raw("SUM(CASE WHEN type='withdrawal' THEN amount ELSE 0 END) as total_withdrawals"),
                DB::raw("COUNT(*) as total_operations")
            )
            ->groupBy('branch_id')
            ->with('branch:id,name,code')
            ->get()
            ->map(fn($row) => [
                'branch_id'         => $row->branch_id,
                'branch_name'       => $row->branch?->name ?? 'Sans agence',
                'branch_code'       => $row->branch?->code ?? '—',
                'total_deposits'    => (float) $row->total_deposits,
                'total_withdrawals' => (float) $row->total_withdrawals,
                'total_operations'  => (int) $row->total_operations,
            ]);

        // ── Top 5 accounts by operations count ──────────────────────────
        $topAccounts = AccountTransaction::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select('customer_account_id', DB::raw('COUNT(*) as ops'), DB::raw('SUM(amount) as volume'))
            ->groupBy('customer_account_id')
            ->orderByDesc('volume')
            ->limit(5)
            ->with('customerAccount:id,account_number,customer_id,balance,currency_id', 'customerAccount.customer:id,full_name,phone', 'customerAccount.currency:id,code,symbol')
            ->get()
            ->map(fn($row) => [
                'account_number' => $row->customerAccount?->account_number,
                'customer_name'  => $row->customerAccount?->customer?->full_name,
                'customer_phone' => $row->customerAccount?->customer?->phone,
                'balance'        => (float) ($row->customerAccount?->balance ?? 0),
                'currency'       => $row->customerAccount?->currency?->code,
                'operations'     => (int) $row->ops,
                'volume'         => (float) $row->volume,
            ]);

        // ── Daily activity (last 30 days) ────────────────────────────────
        $daily = AccountTransaction::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN type='deposit' THEN amount ELSE 0 END) as deposits"),
                DB::raw("SUM(CASE WHEN type='withdrawal' THEN amount ELSE 0 END) as withdrawals"),
                DB::raw('COUNT(*) as operations')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return $this->sendResponse([
            'period'            => ['start' => $startDate, 'end' => $endDate],
            'accounts' => [
                'total'         => $totalAccounts,
                'active'        => $activeAccounts,
                'vip'           => $vipAccounts,
                'in_debt'       => $inDebtAccounts,
                'total_balance' => (float) $totalBalance,
                'total_debt'    => (float) $totalDebt,
                'total_credit_limit' => (float) $totalCredit,
            ],
            'transactions' => [
                'total_deposits'    => (float) $totalDeposits,
                'total_withdrawals' => (float) $totalWithdrawals,
                'count_deposits'    => (int) $countDeposits,
                'count_withdrawals' => (int) $countWithdrawals,
                'total_interest'    => (float) $totalInterest,
                'net_flow'          => (float) ($totalDeposits - $totalWithdrawals),
            ],
            'by_branch'     => $byBranch,
            'top_accounts'  => $topAccounts,
            'daily'         => $daily,
        ]);
    }

    /**
     * Get accounts for a specific customer.
     */
    public function customerAccounts(string $customerId)
    {
        $customer = Customer::find($customerId);

        if (!$customer) {
            return $this->sendErrorResponse('Client non trouvé', 404);
        }

        $accounts = $customer->accounts()
            ->with(['currency', 'branch', 'interestSettings'])
            ->get();

        return CustomerAccountResource::collection($accounts);
    }
}
