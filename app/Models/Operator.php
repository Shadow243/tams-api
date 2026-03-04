<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Media\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

class Operator extends Model
{
    use HasMedia, KeepsDeletedModels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'country_id',
        'logo',
    ];

    /**
     * Get the country that owns the operator.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::registerMediaForProperty(
            property: 'logo',
            directory: 'operators/logos',
            filename: fn ($model) => $model->id . '-' . time()
        );
    }
}
