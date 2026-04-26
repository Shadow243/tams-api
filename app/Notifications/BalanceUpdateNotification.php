<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a balance is updated due to a transaction.
 */
final class BalanceUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $entityType,  // 'branch' or 'wallet'
        public readonly int|string $entityId,
        public readonly string $entityName,
        public readonly float $oldBalance,
        public readonly float $newBalance,
        public readonly float $amount,
        public readonly string $currencyCode,
        public readonly Transaction $transaction,
    ) {}

    /** Deliver via database (persistent) + broadcast (real-time) */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** Payload stored in the `data` JSON column */
    public function toDatabase(object $notifiable): array
    {
        $notificationType = $this->entityType === 'branch' 
            ? NotificationType::BRANCH_BALANCE_UPDATED 
            : NotificationType::WALLET_BALANCE_UPDATED;

        return [
            'type'           => $notificationType->value,
            'title'          => $notificationType->label(),
            'body'           => $this->buildBody(),
            'icon'           => $notificationType->icon(),
            'color'          => $notificationType->color(),
            'resource_type'  => $this->entityType,
            'resource_id'    => $this->entityId,
            'resource_name'  => $this->entityName,
            'old_balance'    => $this->oldBalance,
            'new_balance'    => $this->newBalance,
            'amount'         => $this->amount,
            'currency_code'  => $this->currencyCode,
            'transaction_id' => $this->transaction->uuid,
            'reference'      => $this->transaction->reference,
        ];
    }

    /**
     * Broadcast payload
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $notificationType = $this->entityType === 'branch' 
            ? NotificationType::BRANCH_BALANCE_UPDATED 
            : NotificationType::WALLET_BALANCE_UPDATED;

        return new BroadcastMessage([
            'notification_type' => $notificationType->value,
            'title'             => $notificationType->label(),
            'body'              => $this->buildBody(),
            'icon'              => $notificationType->icon(),
            'color'             => $notificationType->color(),
            'resource_type'     => $this->entityType,
            'resource_id'       => $this->entityId,
            'resource_name'     => $this->entityName,
            'old_balance'       => $this->oldBalance,
            'new_balance'       => $this->newBalance,
            'amount'            => $this->amount,
            'currency_code'     => $this->currencyCode,
            'transaction_id'    => $this->transaction->uuid,
            'reference'         => $this->transaction->reference,
        ]);
    }

    private function buildBody(): string
    {
        $sign = $this->amount >= 0 ? '+' : '';
        $formattedAmount = $sign . number_format(abs($this->amount), 2, ',', ' ');
        $formattedNew = number_format($this->newBalance, 2, ',', ' ');
        
        $entityLabel = $this->entityType === 'branch' ? 'Branche' : 'Wallet';
        
        return "{$entityLabel} {$this->entityName} : {$formattedAmount} {$this->currencyCode}. "
             . "Nouveau solde : {$formattedNew} {$this->currencyCode}. "
             . "Réf: {$this->transaction->reference}";
    }
}
