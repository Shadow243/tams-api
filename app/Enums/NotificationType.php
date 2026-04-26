<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    // ── Transaction events ────────────────────────────────────────────────
    case TRANSACTION_CREATED   = 'transaction_created';
    case TRANSACTION_AVAILABLE = 'transaction_available';
    case TRANSACTION_COMPLETED = 'transaction_completed';
    case TRANSACTION_CANCELLED = 'transaction_cancelled';
    case TRANSACTION_FAILED    = 'transaction_failed';
    case TRANSACTION_EXPIRED   = 'transaction_expired';

    // ── Balance events ────────────────────────────────────────────────────
    case BRANCH_BALANCE_UPDATED = 'branch_balance_updated';
    case WALLET_BALANCE_UPDATED = 'wallet_balance_updated';

    // ── System events ─────────────────────────────────────────────────────
    case SYSTEM_ALERT          = 'system_alert';

    public function label(): string
    {
        return match ($this) {
            self::TRANSACTION_CREATED   => 'Nouvelle transaction',
            self::TRANSACTION_AVAILABLE => 'Transaction disponible',
            self::TRANSACTION_COMPLETED => 'Transaction complétée',
            self::TRANSACTION_CANCELLED => 'Transaction annulée',
            self::TRANSACTION_FAILED    => 'Transaction échouée',
            self::TRANSACTION_EXPIRED   => 'Transaction expirée',
            self::BRANCH_BALANCE_UPDATED => 'Solde branche mis à jour',
            self::WALLET_BALANCE_UPDATED => 'Solde wallet mis à jour',
            self::SYSTEM_ALERT          => 'Alerte système',
        };
    }

    /** Bootstrap / Tabler color class */
    public function color(): string
    {
        return match ($this) {
            self::TRANSACTION_CREATED   => 'primary',
            self::TRANSACTION_AVAILABLE => 'info',
            self::TRANSACTION_COMPLETED => 'success',
            self::TRANSACTION_CANCELLED => 'secondary',
            self::TRANSACTION_FAILED    => 'danger',
            self::TRANSACTION_EXPIRED   => 'dark',
            self::BRANCH_BALANCE_UPDATED => 'success',
            self::WALLET_BALANCE_UPDATED => 'success',
            self::SYSTEM_ALERT          => 'warning',
        };
    }

    /** Tabler icon name */
    public function icon(): string
    {
        return match ($this) {
            self::TRANSACTION_CREATED   => 'ti-plus',
            self::TRANSACTION_AVAILABLE => 'ti-check',
            self::TRANSACTION_COMPLETED => 'ti-circle-check',
            self::TRANSACTION_CANCELLED => 'ti-x',
            self::TRANSACTION_FAILED    => 'ti-alert-circle',
            self::TRANSACTION_EXPIRED   => 'ti-clock-hour-4',
            self::BRANCH_BALANCE_UPDATED => 'ti-cash',
            self::WALLET_BALANCE_UPDATED => 'ti-wallet',
            self::SYSTEM_ALERT          => 'ti-alert-triangle',
        };
    }

    /** Map from TransactionStatus value → NotificationType */
    public static function fromTransactionStatus(string $status): self
    {
        return match ($status) {
            'available' => self::TRANSACTION_AVAILABLE,
            'completed' => self::TRANSACTION_COMPLETED,
            'cancelled' => self::TRANSACTION_CANCELLED,
            'failed'    => self::TRANSACTION_FAILED,
            'expired'   => self::TRANSACTION_EXPIRED,
            default     => self::TRANSACTION_CREATED,
        };
    }
}
