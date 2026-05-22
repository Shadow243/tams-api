<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return match($this->method()) {
            'POST' => $this->user()->can('creer_utilisateurs'),
            'PUT', 'PATCH' => $this->user()->can('editer_utilisateurs'),
            'DELETE' => $this->user()->can('supprimer_utilisateurs'),
            default => false,
        };
    }

    protected function prepareForValidation(): void
    {
        $genderMap = [
            'M' => 'male', 'm' => 'male', 'MALE' => 'male',
            'F' => 'female', 'f' => 'female', 'FEMALE' => 'female',
            'O' => 'other', 'o' => 'other', 'OTHER' => 'other',
            '' => null,
        ];

        $gender = $this->gender;
        if ($gender !== null && array_key_exists($gender, $genderMap)) {
            $this->merge(['gender' => $genderMap[$gender]]);
        } elseif ($gender === '') {
            $this->merge(['gender' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isUpdate = in_array($this->method(), ['PUT', 'PATCH']);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'active' => ['nullable', 'boolean'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                Password::min(8),
                'confirmed',
            ],
            'wallet_ids'   => ['nullable', 'array'],
            'wallet_ids.*' => ['integer', 'exists:wallets,id'],
        ];
    }
}
