<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_types_operations'),
            'PUT', 'PATCH' => $this->user()->can('editer_types_operations'),
            'DELETE' => $this->user()->can('supprimer_types_operations'),
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
        $transactionTypeId = $this->route('transactionType')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('transaction_types', 'code')->ignore($transactionTypeId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
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
            'code' => __('transaction_types.code'),
            'name' => __('transaction_types.name'),
            'description' => __('transaction_types.description'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => __('transaction_types.validation.code_required'),
            'code.unique' => __('transaction_types.validation.code_unique'),
            'code.regex' => __('transaction_types.validation.code_format'),
            'name.required' => __('transaction_types.validation.name_required'),
            'name.max' => __('transaction_types.validation.name_max'),
            'description.max' => __('transaction_types.validation.description_max'),
        ];
    }
}
