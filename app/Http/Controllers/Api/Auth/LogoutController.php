<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @group Auth
 *
 * @subgroup Login
 */
final class LogoutController extends Controller
{
    /**
     * Logout user and invalidate token
     *
     * @response 200 {
     *   "message": "Logged out successfully"
     * }
     */
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        Auth::guard('web')->logout();

        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        return $this->sendMessage(__('auth.logged_out'));
    }

}
