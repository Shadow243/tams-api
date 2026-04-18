<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class SessionController extends Controller
{
    /**
     * List all active sessions (tokens) for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()->id ?? null;
        $tokens = $user->tokens()->get(['id', 'name', 'last_used_at', 'created_at']);
        
        return $this->sendData([
            'sessions' => $tokens,
            'current_session_id' => $currentTokenId
        ]);
    }

    /**
     * Revoke a specific session (token) by ID
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $token = $user->tokens()->find($id);
        if (!$token) {
            return $this->sendErrorResponse('Session not found', 404);
        }
        $token->delete();
        return $this->sendMessage('Session revoked');
    }

    /**
     * Revoke all sessions except the current one
     */
    public function destroyOthers(Request $request)
    {
        $user = $request->user();
        $currentId = $user->currentAccessToken()->id ?? null;
        $user->tokens()->where('id', '!=', $currentId)->delete();
        return $this->sendMessage('All other sessions revoked');
    }
}
