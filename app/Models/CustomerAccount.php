<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomerAccountStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class CustomerAccount extends Model
{
    use HasFactory, HasUuid, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_number',
        'customer_id',
        'currency_id',
        'branch_id',
        'balance',
        'credit_limit',
        'status',
        'is_vip',
        'notes',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_accounts';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CustomerAccountStatus::class,
            'balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'is_vip' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->account_number)) {
                $model->account_number = self::generateAccountNumber();
            }
        });
    }

    /**
     * Generate a unique account number.
     */
    public static function generateAccountNumber(): string
    {
        do {
            $number = 'ACC' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        } while (self::where('account_number', $number)->exists());

        return $number;
    }

    /**
     * Get the customer that owns the account.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the currency of the account.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the branch that manages the account.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the transactions for the account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the interest settings for the account.
     */
    public function interestSettings(): HasOne
    {
        return $this->hasOne(AccountInterestSetting::class);
    }

    /**
     * Check if account is active.
     */
    public function isActive(): bool
    {
        return $this->status === CustomerAccountStatus::ACTIVE;
    }

    /**
     * Check if account has sufficient balance (considering credit limit).
     */
    public function hasSufficientBalance(float $amount): bool
    {
        $availableBalance = $this->balance + $this->credit_limit;
        return $availableBalance >= $amount;
    }

    /**
     * Get available balance (balance + credit limit).
     */
    public function getAvailableBalanceAttribute(): float
    {
        return (float) $this->balance + (float) $this->credit_limit;
    }

    /**
     * Check if account is in debt (negative balance).
     */
    public function isInDebt(): bool
    {
        return $this->balance < 0;
    }

    /**
     * Get the debt amount.
     */
    public function getDebtAmountAttribute(): float
    {
        return $this->balance < 0 ? abs((float) $this->balance) : 0.0;
    }

    /**
     * Scope a query to only include active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', CustomerAccountStatus::ACTIVE);
    }

    /**
     * Scope a query to only include VIP accounts.
     */
    public function scopeVip($query)
    {
        return $query->where('is_vip', true);
    }

    /**
     * Scope a query to only include accounts in debt.
     */
    public function scopeInDebt($query)
    {
        return $query->where('balance', '<', 0);
    }
}
