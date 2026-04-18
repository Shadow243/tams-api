<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchBalance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'branch_balances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'currency_code',
        'cash_balance',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cash_balance' => 'decimal:2',
        ];
    }

    /**
     * Get the branch that owns this balance.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the currency for this balance.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Increase the balance by the given amount
     */
    public function increase(float $amount): void
    {
        $this->cash_balance += $amount;
        $this->save();
    }

    /**
     * Decrease the balance by the given amount
     * 
     * @throws \Exception if balance becomes negative
     */
    public function decrease(float $amount): void
    {
        $newBalance = $this->cash_balance - $amount;
        
        if ($newBalance < 0) {
            throw new \Exception(sprintf(
                'Insufficient %s balance in branch %s. Required: %s, Available: %s',
                $this->currency_code,
                $this->branch->name,
                number_format($amount, 2),
                number_format($this->cash_balance, 2)
            ));
        }
        
        $this->cash_balance = $newBalance;
        $this->save();
    }

    /**
     * Check if balance is sufficient for the given amount
     */
    public function hasSufficient(float $amount): bool
    {
        return $this->cash_balance >= $amount;
    }
}
