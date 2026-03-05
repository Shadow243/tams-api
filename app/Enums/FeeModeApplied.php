<?php

declare(strict_types=1);

namespace App\Enums;

enum FeeModeApplied: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case NEGOTIATED = 'negotiated';
    case MANUAL_OVERRIDE = 'manual_override';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixe',
            self::PERCENTAGE => 'Pourcentage',
            self::NEGOTIATED => 'Négocié',
            self::MANUAL_OVERRIDE => 'Manuel',
        };
    }

    public function isFixed(): bool
    {
        return $this === self::FIXED;
    }

    public function isPercentage(): bool
    {
        return $this === self::PERCENTAGE;
    }

    public function isNegotiated(): bool
    {
        return $this === self::NEGOTIATED;
    }

    public function isManualOverride(): bool
    {
        return $this === self::MANUAL_OVERRIDE;
    }
}
