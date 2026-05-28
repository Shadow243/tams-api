<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OTPHP\TOTP;


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

        $user = User::withoutGlobalScope(\App\Scopes\ActiveScope::class)
            ->where(function ($q) use ($data) {
                $q->where('email', $data['login'])
                  ->orWhere('phone_number', get_parsed_phone_number($data['login']));
            })->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'message' => [__('auth.failed')],
            ]);
        }

        if (! $user->active) {
            return $this->sendErrorResponse(__('auth.account_disabled'), 403);
        }
        
        // Check if 2FA is enabled
        if ($user->two_factor_enabled) {
            // Generate a temporary token for 2FA verification
            $tempToken = Str::random(64);
            
            // Store user info in cache for 5 minutes
            Cache::put("2fa_pending:{$tempToken}", [
                'user_id' => $user->id,
                'device_name' => $data['device_name'],
            ], now()->addMinutes(5));
            
            return $this->sendData([
                'requires_2fa' => true,
                'temp_token' => $tempToken,
                'message' => 'Two-factor authentication code required',
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
        $user = auth('sanctum')->user();
        if (!$user instanceof User) {
            return $this->sendErrorResponse('Unauthorized', 401);
        }
        return $this->sendData(new UserResource($user));
    }

    /**
     * Verify 2FA code and complete login
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function verify2FA(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'temp_token' => 'required|string',
        ]);

        $tempToken = $request->input('temp_token');
        $pendingData = Cache::get("2fa_pending:{$tempToken}");

        if (!$pendingData) {
            return $this->sendErrorResponse('Invalid or expired 2FA session', 422);
        }

        $user = User::find($pendingData['user_id']);

        if (!$user || !$user->two_factor_enabled || !$user->two_factor_secret) {
            Cache::forget("2fa_pending:{$tempToken}");
            return $this->sendErrorResponse('2FA not configured', 422);
        }

        // Verify the code
        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $otp = TOTP::create($secret);

            if (!$otp->verify($request->code)) {
                return $this->sendErrorResponse('Invalid 2FA code', 422);
            }

            // Code is valid, create token
            $token = $user->createToken($pendingData['device_name'] ?? 'Unknown Device')->plainTextToken;

            // Clear cache
            Cache::forget("2fa_pending:{$tempToken}");

            return $this->sendData([
                'user' => new UserResource($user),
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Failed to verify 2FA code', 500);
        }
    }

    /**
     * Validate user password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validatePassword(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!$user) {
            return $this->sendErrorResponse('Unauthorized', 401);
        }

        $valid = Hash::check($request->password, $user->password);

        return $this->sendData([
            'valid' => $valid,
        ]);
    }
}
