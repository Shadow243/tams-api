<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Enums\NotificationType;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransactionNotification;
use Illuminate\Support\Facades\Log;

/**
 * Centralises all notification dispatch logic.
 *
 * Visibility rules
 * ────────────────
 * • ADMINS        (role = admin)       → receive ALL notifications
 * • BRANCH USERS  (role != admin)      → receive only notifications
 *                                        whose branch_id = X
 */
final class NotificationService
{
    /**
     * Dispatch a transaction-lifecycle notification to the relevant users.
     */
    public function notifyTransaction(
        Transaction $transaction,
        NotificationType $type,
    ): void {
        try {
            $recipients = $this->resolveRecipients($transaction);

            foreach ($recipients as $user) {
                $user->notify(new TransactionNotification($transaction, $type));
            }
        } catch (\Throwable $e) {
            // Never let a notification failure bubble up to the request
            Log::error('NotificationService: failed to dispatch', [
                'error'          => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'type'           => $type->value,
            ]);
        }
    }

    /**
     * Resolve which users should receive a notification for the given transaction.
     *
     * Admins (role = admin) see everything regardless of their branch_id.
     * All other users only see notifications for their own branch.
     */
    private function resolveRecipients(Transaction $transaction): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->where(function ($q) use ($transaction) {
                $q->whereHas('roles', fn ($r) => $r->where('name', 'admin')) // admins see all
                  ->orWhere('branch_id', $transaction->branch_id);            // same branch
            })
            ->where('active', true)
            ->get();
    }
}
