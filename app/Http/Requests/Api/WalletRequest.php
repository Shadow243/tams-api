<?php

namespace App\Http\Requests\Api;

use App\Enums\WalletStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_portefeuilles'),
            'PUT', 'PATCH' => $this->user()->can('editer_portefeuilles'),
            'DELETE' => $this->user()->can('supprimer_portefeuilles'),
            default => false,
        };
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $walletId = $this->route('wallet');

        return [
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],
            'operator_id' => [
                'required',
                'integer',
                'exists:operators,id',
            ],
            'wallet_number' => [
                'required',
                'string',
                'max:255',
                // Unique constraint: wallet_number + operator_id + currency must be unique
                Rule::unique('wallets', 'wallet_number')
                    ->where('operator_id', $this->input('operator_id'))
                    ->where('currency', $this->input('currency', 'USD'))
                    ->ignore($walletId),
            ],
            'balance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999999999.99',
            ],
            'currency' => [
                'nullable',
                'string',
                'max:10',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(WalletStatus::values()),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'branch_id' => __('wallets.form.branch'),
            'operator_id' => __('wallets.form.operator'),
            'wallet_number' => __('wallets.form.wallet_number'),
            'balance' => __('wallets.form.balance'),
            'currency' => __('wallets.form.currency'),
            'status' => __('wallets.form.status'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'wallet_number.unique' => __('wallets.validation.wallet_number_unique'),
            'branch_id.exists' => __('wallets.validation.branch_exists'),
            'operator_id.exists' => __('wallets.validation.operator_exists'),
        ];
    }
}
