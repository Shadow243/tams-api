<?php

namespace App\Http\Requests\Api;

use App\Enums\BranchStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_branches'),
            'PUT', 'PATCH' => $this->user()->can('editer_branches'),
            'DELETE' => $this->user()->can('supprimer_branches'),
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
        $branchId = $this->route('branch');

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('branches', 'code')->ignore($branchId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'country_id' => [
                'required',
                'integer',
                'exists:countries,id',
            ],
            'address' => [
                'nullable',
                'string',
                'max:500',
            ],
            'cash_balance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(BranchStatus::values()),
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
            'code.required' => __('Branch code is required'),
            'code.unique' => __('This branch code already exists'),
            'name.required' => __('Branch name is required'),
            'country_id.required' => __('Country is required'),
            'country_id.exists' => __('The selected country does not exist'),
            'cash_balance.numeric' => __('Cash balance must be a valid number'),
            'cash_balance.min' => __('Cash balance cannot be negative'),
            'status.in' => __('Invalid status value'),
        ];
    }
}
