<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CountryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_pays'),
            'PUT', 'PATCH' => $this->user()->can('editer_pays'),
            'DELETE' => $this->user()->can('supprimer_pays'),
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
        $countryId = $this->route('country');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('countries', 'name')->ignore($countryId),
            ],
            'code' => [
                'required',
                'string',
                'max:3',
                // 'alpha',
                Rule::unique('countries', 'code')->ignore($countryId),
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
            'name.required' => __('Country name is required'),
            'name.unique' => __('This country name already exists'),
            'code.required' => __('Country code is required'),
            'code.unique' => __('This country code already exists'),
            'code.alpha' => __('Country code must contain only letters'),
        ];
    }
}
