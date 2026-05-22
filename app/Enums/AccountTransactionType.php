<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountTransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case INTEREST_CREDIT = 'interest_credit';
    case INTEREST_DEBIT = 'interest_debit';
    case FEE = 'fee';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Dépôt',
            self::WITHDRAWAL => 'Retrait',
            self::INTEREST_CREDIT => 'Intérêt Créditeur',
            self::INTEREST_DEBIT => 'Intérêt Débiteur',
            self::FEE => 'Frais',
            self::ADJUSTMENT => 'Ajustement',
        };
    }

    public function sign(): string
    {
        return match ($this) {
            self::DEPOSIT, self::INTEREST_CREDIT, self::ADJUSTMENT => '+',
            self::WITHDRAWAL, self::INTEREST_DEBIT, self::FEE => '-',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DEPOSIT, self::INTEREST_CREDIT => 'success',
            self::WITHDRAWAL, self::INTEREST_DEBIT, self::FEE => 'danger',
            self::ADJUSTMENT => 'info',
        };
    }
}
