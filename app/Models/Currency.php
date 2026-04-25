<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class Currency extends Model
{
    use HasFactory, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'country_id',
        'decimal_places',
        'exchange_rate',
        'is_active',
        'is_default',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currencies';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'exchange_rate' => 'decimal:6',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to get the default currency.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the country that owns the currency.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all transactions for this currency.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'currency_code', 'code');
    }

    /**
     * Get all wallets that use this currency.
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'currency_id');
    }

    /**
     * Get all branch balance records for this currency.
     */
    public function branchBalances()
    {
        return $this->hasMany(BranchBalance::class, 'currency_code', 'code');
    }

    /**
     * Format an amount with the currency symbol.
     */
    public function formatAmount(float $amount): string
    {
        $formattedAmount = number_format($amount, $this->decimal_places, ',', ' ');
        
        // Place symbol based on currency ($ before, others after)
        if (in_array($this->code, ['USD'])) {
            return $this->symbol . ' ' . $formattedAmount;
        }
        
        return $formattedAmount . ' ' . $this->symbol;
    }
}
