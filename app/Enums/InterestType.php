<?php

declare(strict_types=1);

namespace App\Enums;

enum InterestType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Pourcentage',
            self::FIXED => 'Montant Fixe',
        };
    }
}
