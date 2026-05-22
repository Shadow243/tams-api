<?php

declare(strict_types=1);

namespace App\Enums;

enum InterestApplicationPeriod: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Quotidien',
            self::WEEKLY => 'Hebdomadaire',
            self::MONTHLY => 'Mensuel',
            self::QUARTERLY => 'Trimestriel',
            self::YEARLY => 'Annuel',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::MONTHLY => 30,
            self::QUARTERLY => 90,
            self::YEARLY => 365,
        };
    }
}
