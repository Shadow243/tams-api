<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountTransactionType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class AccountTransaction extends Model
{
    use HasFactory, HasUuid, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference',
        'customer_account_id',
        'transaction_id',
        'user_id',
        'branch_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'account_transactions';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountTransactionType::class,
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->reference)) {
                $model->reference = self::generateReference();
            }
        });
    }

    /**
     * Generate a unique reference number.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'AT' . date('YmdHis') . strtoupper(substr(uniqid(), -4));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Get the customer account that owns the transaction.
     */
    public function customerAccount(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    /**
     * Get the related main transaction (if any).
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the user who performed the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if transaction is a credit (increases balance).
     */
    public function isCredit(): bool
    {
        return in_array($this->type, [
            AccountTransactionType::DEPOSIT,
            AccountTransactionType::INTEREST_CREDIT,
            AccountTransactionType::ADJUSTMENT,
        ]);
    }

    /**
     * Check if transaction is a debit (decreases balance).
     */
    public function isDebit(): bool
    {
        return in_array($this->type, [
            AccountTransactionType::WITHDRAWAL,
            AccountTransactionType::INTEREST_DEBIT,
            AccountTransactionType::FEE,
        ]);
    }

    /**
     * Get formatted amount with sign.
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->isCredit() ? '+' : '-';
        return $sign . number_format((float) $this->amount, 2);
    }
}
