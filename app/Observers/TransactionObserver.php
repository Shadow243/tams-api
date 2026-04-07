<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\NotificationType;
use App\Models\Transaction;
use App\Services\Api\NotificationService;

/**
 * Fires notifications on every transaction lifecycle transition.
 */
final class TransactionObserver
{
    public function __construct(private NotificationService $notificationService) {}

    /** New transaction created → notify as PENDING */
    public function created(Transaction $transaction): void
    {
        $this->notificationService->notifyTransaction(
            $transaction,
            NotificationType::TRANSACTION_CREATED,
        );
    }

    /** Status changed → resolve the right NotificationType */
    public function updated(Transaction $transaction): void
    {
        if (! $transaction->wasChanged('status')) {
            return;
        }

        $type = NotificationType::fromTransactionStatus(
            $transaction->status->value
        );

        // Skip TRANSACTION_CREATED on update (already handled in created())
        if ($type === NotificationType::TRANSACTION_CREATED) {
            return;
        }

        $this->notificationService->notifyTransaction($transaction, $type);
    }
}
