<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\FeeModeApplied;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_transactions'),
            'PUT', 'PATCH' => $this->user()->can('editer_transactions'),
            'DELETE' => $this->user()->can('supprimer_transactions'),
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
        $rules = [
            'transaction_type_id' => [
                'required',
                'integer',
                'exists:transaction_types,id',
            ],
            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],
            'destination_branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
                'different:branch_id',
            ],
            'customer_id' => [
                'nullable',
                // 'uuid',
                'exists:customers,id',
            ],
            'wallet_id' => [
                'nullable',
                'integer',
                'exists:wallets,id',
            ],
            'dest_wallet_id' => [
                'nullable',
                'integer',
                'exists:wallets,id',
                'different:wallet_id',
            ],
            'currency_id' => [
                'nullable',
                'integer',
                'exists:currencies,id',
            ],
            'customer_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+?[0-9]{8,20}$/',
            ],
            'gross_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
            'fee_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
            'fee_mode_applied' => [
                'nullable',
                'string',
                Rule::in(array_map(fn($case) => $case->value, FeeModeApplied::cases())),
            ],
            'fee_rule_id' => [
                'nullable',
                'uuid',
                'exists:fee_rules,id',
            ],
            'parent_transaction_id' => [
                'nullable',
                'uuid',
                'exists:transactions,id',
            ],
            'withdrawal_code' => [
                'nullable',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
                Rule::unique('transactions', 'withdrawal_code')->ignore($this->transaction),
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(array_map(fn($case) => $case->value, TransactionStatus::cases())),
            ],
        ];

        // On update, we can change status
        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            // Make most fields optional on update
            foreach ($rules as $key => $rule) {
                if (is_array($rule) && isset($rule[0]) && $rule[0] === 'required') {
                    $rules[$key][0] = 'sometimes';
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'transaction_type_id' => __('transaction type'),
            'branch_id' => __('branch'),
            'destination_branch_id' => __('destination branch'),
            'customer_id' => __('customer'),
            'wallet_id' => __('wallet'),
            'customer_phone' => __('customer phone'),
            'gross_amount' => __('gross amount'),
            'fee_amount' => __('fee amount'),
            'fee_mode_applied' => __('fee mode applied'),
            'fee_rule_id' => __('fee rule'),
            'parent_transaction_id' => __('parent transaction'),
            'withdrawal_code' => __('withdrawal code'),
            'expires_at' => __('expiration date'),
            'status' => __('status'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-calculate net amount if not provided
        if ($this->has('gross_amount') && $this->has('fee_amount')) {
            $this->merge([
                'net_amount' => (float) $this->gross_amount - (float) ($this->fee_amount ?? 0),
            ]);
        }
    }
}
