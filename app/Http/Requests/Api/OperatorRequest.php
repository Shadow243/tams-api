<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperatorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_operateurs'),
            'PUT', 'PATCH' => $this->user()->can('editer_operateurs'),
            'DELETE' => $this->user()->can('supprimer_operateurs'),
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
        $operatorId = $this->route('operator');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('operators', 'name')->ignore($operatorId),
            ],
            'country_id' => [
                'required',
                'integer',
                'exists:countries,id',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,svg',
                'max:2048',
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
            'name.required' => __('Operator name is required'),
            'name.unique' => __('This operator name already exists'),
            'country_id.required' => __('Country is required'),
            'country_id.exists' => __('Selected country does not exist'),
            'logo.image' => __('Logo must be an image'),
            'logo.max' => __('Logo size must not exceed 2MB'),
        ];
    }
}
