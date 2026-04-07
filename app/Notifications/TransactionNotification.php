<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every user who should be aware of a transaction lifecycle event.
 * Stored in the native Laravel `notifications` table (via the `database` channel).
 */
final class TransactionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Transaction $transaction,
        public readonly NotificationType $notificationType,
    ) {}

    /** Deliver via database (persistent) + broadcast (real-time) */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** Payload stored in the `data` JSON column */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => $this->notificationType->value,
            'title'          => $this->notificationType->label(),
            'body'           => $this->buildBody(),
            'icon'           => $this->notificationType->icon(),
            'color'          => $this->notificationType->color(),
            'resource_type'  => 'transaction',
            'resource_id'    => $this->transaction->uuid,
            'reference'      => $this->transaction->reference,
            'amount'         => (float) $this->transaction->gross_amount,
            'currency_code'  => $this->transaction->currency_code,
            'status'         => $this->transaction->status->value,
            'branch_id'      => $this->transaction->branch_id,
        ];
    }

    /**
     * Broadcast payload — `notification_type` avoids collision with Laravel's own `type` key.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification_type' => $this->notificationType->value,
            'title'             => $this->notificationType->label(),
            'body'              => $this->buildBody(),
            'icon'              => $this->notificationType->icon(),
            'color'             => $this->notificationType->color(),
            'resource_type'     => 'transaction',
            'resource_id'       => $this->transaction->uuid,
            'reference'         => $this->transaction->reference,
            'amount'            => (float) $this->transaction->gross_amount,
            'currency_code'     => $this->transaction->currency_code,
            'status'            => $this->transaction->status->value,
            'branch_id'         => $this->transaction->branch_id,
        ]);
    }

    private function buildBody(): string
    {
        $ref    = $this->transaction->reference;
        $amount = number_format((float) $this->transaction->gross_amount, 2, ',', ' ');
        $code   = $this->transaction->currency_code;

        return match ($this->notificationType) {
            NotificationType::TRANSACTION_CREATED   => "Transaction {$ref} créée — {$amount} {$code}",
            NotificationType::TRANSACTION_AVAILABLE => "Transaction {$ref} disponible — {$amount} {$code}",
            NotificationType::TRANSACTION_COMPLETED => "Transaction {$ref} complétée — {$amount} {$code}",
            NotificationType::TRANSACTION_CANCELLED => "Transaction {$ref} annulée.",
            NotificationType::TRANSACTION_FAILED    => "Transaction {$ref} a échoué.",
            NotificationType::TRANSACTION_EXPIRED   => "Transaction {$ref} a expiré.",
            default => "Transaction {$ref} mise à jour.",
        };
    }
}
