<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AccountInterestSettingResource;
use App\Models\AccountInterestSetting;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @group Account Interest Settings
 */
class AccountInterestSettingController extends Controller
{
    /**
     * Store or update interest settings for an account.
     */
    public function store(Request $request, string $accountIdentifier)
    {
        $account = CustomerAccount::where('uuid', $accountIdentifier)
            ->orWhere('account_number', $accountIdentifier)
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        $validator = Validator::make($request->all(), [
            'interest_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'interest_rate' => 'required_if:interest_type,percentage|nullable|numeric|min:0|max:100',
            'fixed_amount' => 'required_if:interest_type,fixed|nullable|numeric|min:0',
            'application_period' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])],
            'apply_on_negative_balance' => 'boolean',
            'apply_on_positive_balance' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse($validator->errors()->first(), 422);
        }

        $data = $validator->validated();
        $data['customer_account_id'] = $account->id;

        // Update or create
        $settings = $account->interestSettings;

        if ($settings) {
            $settings->update($data);
            $message = 'Paramètres d\'intérêts mis à jour avec succès';
        } else {
            $settings = AccountInterestSetting::create($data);
            $message = 'Paramètres d\'intérêts créés avec succès';
        }

        $settings->load('customerAccount.customer');

        return $this->sendResponse(
            new AccountInterestSettingResource($settings),
            $message
        );
    }

    /**
     * Display the interest settings for an account.
     */
    public function show(string $accountIdentifier)
    {
        $account = CustomerAccount::where('uuid', $accountIdentifier)
            ->orWhere('account_number', $accountIdentifier)
            ->with('interestSettings')
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        if (!$account->interestSettings) {
            return $this->sendErrorResponse('Aucun paramètre d\'intérêts configuré', 404);
        }

        $account->interestSettings->load('customerAccount.customer');

        return $this->sendData(
            new AccountInterestSettingResource($account->interestSettings)
        );
    }

    /**
     * Delete interest settings for an account.
     */
    public function destroy(string $accountIdentifier)
    {
        $account = CustomerAccount::where('uuid', $accountIdentifier)
            ->orWhere('account_number', $accountIdentifier)
            ->with('interestSettings')
            ->first();

        if (!$account) {
            return $this->sendErrorResponse('Compte client non trouvé', 404);
        }

        if (!$account->interestSettings) {
            return $this->sendErrorResponse('Aucun paramètre d\'intérêts configuré', 404);
        }

        $account->interestSettings->delete();

        return $this->sendResponse(
            null,
            'Paramètres d\'intérêts supprimés avec succès'
        );
    }
}
