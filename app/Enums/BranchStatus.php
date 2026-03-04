<?php

declare(strict_types=1);

namespace App\Enums;

enum BranchStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

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
     * Get the label for the status
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::INACTIVE => 'Inactif',
        };
    }

    /**
     * Check if the status is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if the status is inactive
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }
}
