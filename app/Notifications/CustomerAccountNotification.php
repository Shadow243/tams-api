<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\AccountTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

final class CustomerAccountNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AccountTransaction $accountTransaction,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $type = $this->notificationType();

        return [
            'type'           => $type->value,
            'title'          => $type->label(),
            'body'           => $this->buildBody(),
            'icon'           => $type->icon(),
            'color'          => $type->color(),
            'resource_type'  => 'customer_account',
            'resource_id'    => $this->accountTransaction->customer_account_id,
            'resource_name'  => $this->accountTransaction->customerAccount?->account_number,
            'amount'         => (float) $this->accountTransaction->amount,
            'balance_before' => (float) $this->accountTransaction->balance_before,
            'balance_after'  => (float) $this->accountTransaction->balance_after,
            'currency_code'  => $this->accountTransaction->customerAccount?->currency?->code ?? 'XAF',
            'reference'      => $this->accountTransaction->reference,
            'branch_id'      => $this->accountTransaction->branch_id,
            'branch_name'    => $this->accountTransaction->branch?->name,
            'customer_name'  => $this->accountTransaction->customerAccount?->customer?->full_name,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    private function notificationType(): NotificationType
    {
        return $this->accountTransaction->isCredit()
            ? NotificationType::CUSTOMER_ACCOUNT_DEPOSIT
            : NotificationType::CUSTOMER_ACCOUNT_WITHDRAWAL;
    }

    private function buildBody(): string
    {
        $tx       = $this->accountTransaction;
        $account  = $tx->customerAccount;
        $customer = $account?->customer?->full_name ?? 'Client inconnu';
        $amount   = number_format((float) $tx->amount, 2, ',', ' ');
        $currency = $account?->currency?->code ?? 'XAF';
        $newBal   = number_format((float) $tx->balance_after, 2, ',', ' ');
        $branch   = $tx->branch?->name ?? '—';
        $action   = $tx->isCredit() ? 'Dépôt' : 'Retrait';

        return "{$action} de {$amount} {$currency} — {$customer} ({$account?->account_number}). "
             . "Nouveau solde: {$newBal} {$currency}. Agence: {$branch}. Réf: {$tx->reference}";
    }
}
