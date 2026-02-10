<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'], // can be email or phone
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'login' => [
                'description' => 'Email or phone number',
                'example' => 'example@example.com or +243993002040',
                'required' => true,
            ],
            'password' => [
                'description' => 'Password',
                'example' => 'password',
                'required' => true,
            ],
            'device_name' => [
                'description' => 'unique device identifier',
                'example' => 'iPhone 16',
                'required' => true,
            ],
        ];
    }
}
