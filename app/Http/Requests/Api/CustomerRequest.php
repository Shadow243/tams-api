<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer');

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'regex:/^[0-9]{7,15}$/',
                'unique:customers,phone,' . $customerId,
            ],
            'national_id' => [
                'nullable',
                'string',
                'max:50',
                'unique:customers,national_id,' . $customerId,
            ],
        ];

        // For update requests, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            foreach ($rules as $key => $rule) {
                if (is_array($rule) && in_array('required', $rule)) {
                    $rules[$key] = array_diff($rule, ['required']);
                    array_unshift($rules[$key], 'sometimes');
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
            'full_name' => __('messages.full_name'),
            'phone' => __('messages.phone'),
            'national_id' => __('messages.national_id'),
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
            'phone.regex' => __('messages.phone_format_invalid'),
            'phone.unique' => __('messages.phone_already_exists'),
            'national_id.unique' => __('messages.national_id_already_exists'),
        ];
    }
}
