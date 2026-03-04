<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    /**
     * Get the label for the wallet status.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('wallets.status.active'),
            self::INACTIVE => __('wallets.status.inactive'),
        };
    }

    /**
     * Check if the wallet is active.
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get all available status values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
