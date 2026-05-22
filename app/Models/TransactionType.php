<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class TransactionType extends Model
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
        'description',
        'branch_effect',
        'branch_amount',
        'wallet_effect',
        'wallet_amount',
        'dest_branch_effect',
        'dest_branch_amount',
        'dest_wallet_effect',
        'dest_wallet_amount',
        'customer_account_effect',
        'customer_account_amount',
        'requires_dest_customer',
    ];

    protected $table = 'transaction_types';

    protected function casts(): array
    {
        return [
            'requires_dest_customer' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include specific code.
     */
    public function scopeByCode(\Illuminate\Database\Eloquent\Builder $query, string $code)
    {
        return $query->where('code', $code);
    }
}
