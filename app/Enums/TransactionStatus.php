<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case AVAILABLE = 'available';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::AVAILABLE => 'Disponible',
            self::COMPLETED => 'Complétée',
            self::CANCELLED => 'Annulée',
            self::FAILED => 'Échouée',
            self::EXPIRED => 'Expirée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::AVAILABLE => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'secondary',
            self::FAILED => 'danger',
            self::EXPIRED => 'dark',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }

    public function canBeModified(): bool
    {
        return in_array($this, [self::PENDING, self::AVAILABLE]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::AVAILABLE]);
    }
}
