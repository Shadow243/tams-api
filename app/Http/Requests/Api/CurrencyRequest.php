<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_devises'),
            'PUT', 'PATCH' => $this->user()->can('editer_devises'),
            'DELETE' => $this->user()->can('supprimer_devises'),
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
        $currencyCode = $this->route('code');

        return [
            'code' => [
                'required',
                'string',
                'size:3',
                'uppercase',
                Rule::unique('currencies', 'code')->ignore($currencyCode, 'code'),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'symbol' => [
                'required',
                'string',
                'max:10',
            ],
            'country_id' => [
                'nullable',
                'integer',
                'exists:countries,id',
            ],
            'decimal_places' => [
                'required',
                'integer',
                'min:0',
                'max:4',
            ],
            'exchange_rate' => [
                'required',
                'numeric',
                'min:0.000001',
                'max:999999999.999999',
            ],
            'is_active' => [
                'boolean',
            ],
            'is_default' => [
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
            'code.required' => __('currencies.validation.code_required'),
            'code.size' => __('currencies.validation.code_size'),
            'code.uppercase' => __('currencies.validation.code_uppercase'),
            'code.unique' => __('currencies.validation.code_unique'),
            'name.required' => __('currencies.validation.name_required'),
            'name.max' => __('currencies.validation.name_max'),
            'symbol.required' => __('currencies.validation.symbol_required'),
            'symbol.max' => __('currencies.validation.symbol_max'),
            'country_id.integer' => __('currencies.validation.country_id_integer'),
            'country_id.exists' => __('currencies.validation.country_id_exists'),
            'decimal_places.required' => __('currencies.validation.decimal_places_required'),
            'decimal_places.integer' => __('currencies.validation.decimal_places_integer'),
            'decimal_places.min' => __('currencies.validation.decimal_places_min'),
            'decimal_places.max' => __('currencies.validation.decimal_places_max'),
            'exchange_rate.required' => __('currencies.validation.exchange_rate_required'),
            'exchange_rate.numeric' => __('currencies.validation.exchange_rate_numeric'),
            'exchange_rate.min' => __('currencies.validation.exchange_rate_min'),
            'exchange_rate.max' => __('currencies.validation.exchange_rate_max'),
            'is_active.boolean' => __('currencies.validation.is_active_boolean'),
            'is_default.boolean' => __('currencies.validation.is_default_boolean'),
        ];
    }
}
