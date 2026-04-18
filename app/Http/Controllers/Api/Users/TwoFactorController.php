<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use OTPHP\TOTP;

class TwoFactorController extends Controller
{
    /**
     * Enable 2FA for the authenticated user
     */
    public function enable(Request $request)
    {
        $user = $request->user();
        if ($user->two_factor_enabled) {
            return $this->sendErrorResponse('2FA already enabled', 422);
        }
        
        // Generate a 32-character secret (Base32 encoded, 160 bits)
        $secret = $this->generateBase32Secret(32);
        
        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_enabled = false;
        $user->save();
        
        $otp = TOTP::create($secret);
        $otp->setLabel($user->email);
        $otp->setIssuer(config('app.name'));
        $qr = $otp->getProvisioningUri();
        
        return $this->sendData([
            'secret' => $secret,
            'qr' => $qr,
        ]);
    }
    
    /**
     * Generate a Base32 encoded secret
     */
    private function generateBase32Secret(int $length = 32): string
    {
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $base32Chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Confirm 2FA setup
     */
    public function confirm(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendErrorResponse($validator->errors()->first(), 422);
        }
        $secret = Crypt::decryptString($user->two_factor_secret);
        $otp = TOTP::create($secret);
        if (!$otp->verify($request->code)) {
            return $this->sendErrorResponse('Invalid code', 422);
        }
        $user->two_factor_enabled = true;
        $user->save();
        return $this->sendMessage('2FA enabled successfully');
    }

    /**
     * Disable 2FA
     */
    public function disable(Request $request)
    {
        $user = $request->user();
        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->save();
        return $this->sendMessage('2FA disabled');
    }

    /**
     * Verify a 2FA code (for login or sensitive actions)
     */
    public function verify(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->sendErrorResponse($validator->errors()->first(), 422);
        }
        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return $this->sendErrorResponse('2FA not enabled', 422);
        }
        $secret = Crypt::decryptString($user->two_factor_secret);
        $otp = TOTP::create($secret);
        if (!$otp->verify($request->code)) {
            return $this->sendErrorResponse('Invalid code', 422);
        }
        return $this->sendMessage('2FA code valid');
    }
}
