<?php

declare(strict_types=1);

namespace App\Enums;

enum FeeMode: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case NEGOTIABLE = 'negotiable';

    /**
     * Get all values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the label for the fee mode
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixe',
            self::PERCENTAGE => 'Pourcentage',
            self::NEGOTIABLE => 'Négociable',
        };
    }

    /**
     * Check if the fee mode is fixed
     *
     * @return bool
     */
    public function isFixed(): bool
    {
        return $this === self::FIXED;
    }

    /**
     * Check if the fee mode is percentage
     *
     * @return bool
     */
    public function isPercentage(): bool
    {
        return $this === self::PERCENTAGE;
    }

    /**
     * Check if the fee mode is negotiable
     *
     * @return bool
     */
    public function isNegotiable(): bool
    {
        return $this === self::NEGOTIABLE;
    }
}
