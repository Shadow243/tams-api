<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WalletStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class Wallet extends Model
{
    use HasFactory, HasUuid, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'branch_id',
        'operator_id',
        'wallet_number',
        'virtual_balance',
        'currency_id',
        'status',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallets';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WalletStatus::class,
            'virtual_balance' => 'decimal:2',
        ];
    }

    /**
     * Get the branch that owns the wallet.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the operator that owns the wallet.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Get the currency that owns the wallet.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Scope a query to only include active wallets.
     */
    public function scopeActive($query)
    {
        return $query->where('status', WalletStatus::ACTIVE);
    }

    /**
     * Scope a query to only include inactive wallets.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', WalletStatus::INACTIVE);
    }

    /**
     * Check if wallet has sufficient virtual balance
     */
    public function hasSufficientVirtualBalance(float $amount): bool
    {
        return $this->virtual_balance >= $amount;
    }

    /**
     * Get formatted virtual balance with currency
     */
    public function getFormattedBalanceAttribute(): string
    {
        $currencySymbol = $this->currency ? $this->currency->symbol : '';
        return number_format((float) $this->virtual_balance, 2) . ' ' . $currencySymbol;
    }
}
