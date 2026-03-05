<?php

namespace App\Http\Requests\Api;

use App\Enums\FeeMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_regles_frais'),
            'PUT', 'PATCH' => $this->user()->can('editer_regles_frais'),
            'DELETE' => $this->user()->can('supprimer_regles_frais'),
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
        return [
            'transaction_type_id' => [
                'required',
                'integer',
                'exists:transaction_types,id',
            ],
            'operator_id' => [
                'nullable',
                'integer',
                'exists:operators,id',
            ],
            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],
            'fee_mode' => [
                'required',
                'string',
                Rule::in(FeeMode::values()),
            ],
            'value' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                Rule::requiredIf(function () {
                    return in_array($this->fee_mode, ['fixed', 'percentage']);
                }),
            ],
            'min_fee' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $this->max_fee !== null && $value >= $this->max_fee) {
                        $fail(__('Minimum fee must be less than maximum fee'));
                    }
                },
            ],
            'max_fee' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $this->min_fee !== null && $value <= $this->min_fee) {
                        $fail(__('Maximum fee must be greater than minimum fee'));
                    }
                },
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
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
            'transaction_type_id.required' => __('Transaction type is required'),
            'transaction_type_id.exists' => __('The selected transaction type does not exist'),
            'operator_id.exists' => __('The selected operator does not exist'),
            'branch_id.exists' => __('The selected branch does not exist'),
            'fee_mode.required' => __('Fee mode is required'),
            'fee_mode.in' => __('Invalid fee mode value'),
            'value.required_if' => __('Value is required for fixed and percentage fee modes'),
            'value.numeric' => __('Value must be a valid number'),
            'value.min' => __('Value cannot be negative'),
            'min_fee.numeric' => __('Minimum fee must be a valid number'),
            'min_fee.min' => __('Minimum fee cannot be negative'),
            'min_fee.lt' => __('Minimum fee must be less than maximum fee'),
            'max_fee.numeric' => __('Maximum fee must be a valid number'),
            'max_fee.min' => __('Maximum fee cannot be negative'),
            'max_fee.gt' => __('Maximum fee must be greater than minimum fee'),
            'is_active.boolean' => __('Is active must be a boolean value'),
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
            'transaction_type_id' => __('transaction type'),
            'operator_id' => __('operator'),
            'branch_id' => __('branch'),
            'fee_mode' => __('fee mode'),
            'value' => __('value'),
            'min_fee' => __('minimum fee'),
            'max_fee' => __('maximum fee'),
            'is_active' => __('is active'),
        ];
    }
}
