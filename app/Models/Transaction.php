<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeeModeApplied;
use App\Enums\TransactionStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class Transaction extends Model
{
    use HasFactory, HasUuid, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'id',
        'reference',
        'transaction_type_id',
        'branch_id',
        'destination_branch_id',
        'user_id',
        'customer_id',
        'wallet_id',
        'customer_phone',
        'gross_amount',
        'fee_amount',
        'net_amount',
        'currency_code',
        'currency_id',
        'fee_rule_id',
        'fee_mode_applied',
        'fee_snapshot',
        'parent_transaction_id',
        'withdrawal_code',
        'expires_at',
        'status',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'fee_mode_applied' => FeeModeApplied::class,
            'fee_snapshot' => 'array',
            'status' => TransactionStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the transaction type that owns the transaction.
     */
    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    /**
     * Get the branch that owns the transaction.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the destination branch for the transaction.
     */
    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    /**
     * Get the user that created the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer that owns the transaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the wallet associated with the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the currency for the transaction.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the fee rule that was applied to this transaction.
     */
    public function feeRule(): BelongsTo
    {
        return $this->belongsTo(FeeRule::class);
    }

    /**
     * Get the parent transaction.
     */
    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    /**
     * Get the child transactions.
     */
    public function childTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }

    /**
     * Scope a query to only include transactions with a specific status.
     */
    public function scopeWithStatus(Builder $query, TransactionStatus|string $status): void
    {
        if (is_string($status)) {
            $query->where('status', $status);
        } else {
            $query->where('status', $status->value);
        }
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', TransactionStatus::PENDING->value);
    }

    /**
     * Scope a query to only include available transactions.
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', TransactionStatus::AVAILABLE->value);
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', TransactionStatus::COMPLETED->value);
    }

    /**
     * Scope a query to only include transactions by transaction type.
     */
    public function scopeByTransactionType(Builder $query, int $transactionTypeId): void
    {
        $query->where('transaction_type_id', $transactionTypeId);
    }

    /**
     * Scope a query to only include transactions by branch.
     */
    public function scopeByBranch(Builder $query, int $branchId): void
    {
        $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query to only include transactions by user.
     */
    public function scopeByUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include transactions by customer.
     */
    public function scopeByCustomer(Builder $query, string $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    /**
     * Scope a query to only include transactions by customer phone.
     */
    public function scopeByCustomerPhone(Builder $query, string $phone): void
    {
        $query->where('customer_phone', $phone);
    }

    /**
     * Scope a query to only include expired transactions.
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('expires_at', '<=', now())
            ->whereIn('status', [TransactionStatus::PENDING->value, TransactionStatus::AVAILABLE->value]);
    }

    /**
     * Scope a query to filter transactions by date range.
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if the transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING;
    }

    /**
     * Check if the transaction is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === TransactionStatus::AVAILABLE;
    }

    /**
     * Check if the transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED;
    }

    /**
     * Check if the transaction is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === TransactionStatus::CANCELLED;
    }

    /**
     * Check if the transaction is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === TransactionStatus::FAILED;
    }

    /**
     * Check if the transaction is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === TransactionStatus::EXPIRED || 
               ($this->expires_at && $this->expires_at->isPast() && !$this->isCompleted());
    }

    /**
     * Check if the transaction can be modified.
     */
    public function canBeModified(): bool
    {
        return $this->status->canBeModified();
    }

    /**
     * Check if the transaction can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Generate a unique reference for the transaction.
     */
    public static function generateReference(string $prefix = 'TXN'): string
    {
        do {
            $reference = $prefix . '-' . strtoupper(uniqid());
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Generate a unique withdrawal code.
     */
    public static function generateWithdrawalCode(): string
    {
        do {
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('withdrawal_code', $code)->exists());

        return $code;
    }
}
