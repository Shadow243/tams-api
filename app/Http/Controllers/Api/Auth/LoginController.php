<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


/**
 * @group Auth
 *
 * @unauthenticated
 *
 * @subgroup Login
 */

final class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::findByEmailOrPhone($data['login']);
        if (! $user) {
            return $this->sendErrorResponse(__('auth.failed'));
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'message' => [__('auth.failed')],
            ]);
        }
        // if ($user->email && ! $user->hasVerifiedEmail()) {
        //     return response()->json(['message' => __('auth.email_not_verified')], 403);
        // }
        // if ($user->phone_number && ! $user->hasVerifiedPhone()) {
        //     return $this->sendErrorResponse(__('auth.phone_not_verified'), 422);
        // }
        $token = $user->createToken($data['device_name'])->plainTextToken;

        // $user->load(['']);

        return $this->sendData([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function me()
    {
        return $this->sendData(new UserResource(auth()->user()));
    }
}
