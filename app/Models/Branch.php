<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BranchStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class Branch extends Model
{
    use HasFactory, HasUuid, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'country_id',
        'address',
        'cash_balance',
        'status',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'branches';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BranchStatus::class,
            'cash_balance' => 'decimal:2',
        ];
    }

    /**
     * Get the country that owns the branch.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all balances for this branch (multi-currency support)
     */
    public function balances(): HasMany
    {
        return $this->hasMany(BranchBalance::class);
    }

    /**
     * Get or create a balance record for the specified currency
     */
    public function getOrCreateBalance(string $currencyCode): BranchBalance
    {
        return $this->balances()->firstOrCreate(
            ['currency_code' => $currencyCode],
            ['cash_balance' => 0]
        );
    }

    /**
     * Get the balance for a specific currency
     */
    public function getBalance(string $currencyCode): float
    {
        $balance = $this->balances()->where('currency_code', $currencyCode)->first();
        return $balance ? (float) $balance->cash_balance : 0.0;
    }

    /**
     * Check if branch has sufficient balance in the specified currency
     */
    public function hasSufficientBalance(string $currencyCode, float $amount): bool
    {
        return $this->getBalance($currencyCode) >= $amount;
    }

    /**
     * Check if the branch is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === BranchStatus::ACTIVE;
    }

    /**
     * Check if the branch is inactive
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === BranchStatus::INACTIVE;
    }

    /**
     * Scope a query to only include active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('status', BranchStatus::ACTIVE);
    }

    /**
     * Scope a query to only include inactive branches.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', BranchStatus::INACTIVE);
    }
}
