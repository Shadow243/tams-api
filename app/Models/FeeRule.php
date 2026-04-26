<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeMode;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class FeeRule extends Model
{
    use HasFactory, HasUuid, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_type_id',
        'operator_id',
        'branch_id',
        'fee_mode',
        'value',
        'min_fee',
        'max_fee',
        'is_active',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fee_rules';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee_mode' => FeeMode::class,
            'value' => 'decimal:2',
            'min_fee' => 'decimal:2',
            'max_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the transaction type that owns the fee rule.
     */
    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    /**
     * Get the operator that owns the fee rule.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Get the branch that owns the fee rule.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if the fee rule is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if the fee rule is inactive
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->is_active === false;
    }

    /**
     * Scope a query to only include active fee rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive fee rules.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to filter by transaction type.
     */
    public function scopeByTransactionType($query, int $transactionTypeId)
    {
        return $query->where('transaction_type_id', $transactionTypeId);
    }

    /**
     * Scope a query to filter by operator.
     */
    public function scopeByOperator($query, ?int $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    /**
     * Scope a query to filter by branch.
     */
    public function scopeByBranch($query, ?int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to filter by fee mode.
     */
    public function scopeByFeeMode($query, string $feeMode)
    {
        return $query->where('fee_mode', $feeMode);
    }

    /**
     * Calculate the fee based on the rule and amount
     *
     * @param float $amount
     * @return float
     */
    public function calculateFee(float $amount): float
    {
        if (!$this->isActive()) {
            return 0;
        }

        $fee = match ($this->fee_mode) {
            FeeMode::FIXED => (float) ($this->value ?? 0),
            FeeMode::PERCENTAGE => $amount * ((float) ($this->value ?? 0) / 100),
            FeeMode::NEGOTIABLE => (float) ($this->min_fee ?? 0), // Default to min_fee for negotiable
            default => 0,
        };

        // Apply min/max constraints if set
        if ($this->min_fee !== null && $fee < (float) $this->min_fee) {
            $fee = (float) $this->min_fee;
        }

        if ($this->max_fee !== null && $fee > (float) $this->max_fee) {
            $fee = (float) $this->max_fee;
        }

        return round($fee, 2);
    }
}
