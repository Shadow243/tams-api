<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InterestApplicationPeriod;
use App\Enums\InterestType;
use App\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class AccountInterestSetting extends Model
{
    use HasFactory, HasUuid, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_account_id',
        'interest_type',
        'interest_rate',
        'fixed_amount',
        'application_period',
        'apply_on_negative_balance',
        'apply_on_positive_balance',
        'last_applied_at',
        'next_application_date',
        'is_active',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'account_interest_settings';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interest_type' => InterestType::class,
            'interest_rate' => 'decimal:4',
            'fixed_amount' => 'decimal:2',
            'application_period' => InterestApplicationPeriod::class,
            'apply_on_negative_balance' => 'boolean',
            'apply_on_positive_balance' => 'boolean',
            'last_applied_at' => 'datetime',
            'next_application_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->next_application_date)) {
                $model->next_application_date = $model->calculateNextApplicationDate();
            }
        });
    }

    /**
     * Get the customer account that owns the interest settings.
     */
    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    /**
     * Calculate the interest amount for a given balance.
     * 
     * For debts (negative balance), interest is calculated on the DEBT AMOUNT only.
     * Example: If balance = -500$ (debt of 500$), interest is calculated on 500$, not on the total withdrawn.
     * 
     * @param float $balance The current account balance (can be negative)
     * @return float The interest amount to apply
     */
    public function calculateInterestAmount(float $balance): float
    {
        if ($this->interest_type === InterestType::PERCENTAGE) {
            // Use abs() to calculate interest on the debt amount (absolute value of negative balance)
            // Example: balance = -500$ → interest on 500$, not on total withdrawn
            return abs($balance) * ((float) $this->interest_rate / 100);
        }

        return (float) $this->fixed_amount;
    }

    /**
     * Check if interest should be applied to current balance.
     */
    public function shouldApplyInterest(float $balance): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if it's time to apply interest
        if ($this->next_application_date && Carbon::now()->lt($this->next_application_date)) {
            return false;
        }

        // Check balance conditions
        if ($balance < 0 && $this->apply_on_negative_balance) {
            return true;
        }

        if ($balance > 0 && $this->apply_on_positive_balance) {
            return true;
        }

        return false;
    }

    /**
     * Calculate the next application date based on period.
     */
    public function calculateNextApplicationDate(?Carbon $from = null): Carbon
    {
        $from = $from ?? Carbon::now();

        return match ($this->application_period) {
            InterestApplicationPeriod::DAILY => $from->addDay(),
            InterestApplicationPeriod::WEEKLY => $from->addWeek(),
            InterestApplicationPeriod::MONTHLY => $from->addMonth(),
            InterestApplicationPeriod::QUARTERLY => $from->addMonths(3),
            InterestApplicationPeriod::YEARLY => $from->addYear(),
        };
    }

    /**
     * Mark interest as applied and update next application date.
     */
    public function markAsApplied(): void
    {
        $this->update([
            'last_applied_at' => Carbon::now(),
            'next_application_date' => $this->calculateNextApplicationDate(),
        ]);
    }

    /**
     * Scope a query to only include active settings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include settings that need to be applied.
     */
    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('next_application_date')
                    ->orWhere('next_application_date', '<=', Carbon::now());
            });
    }
}
