<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Country extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::created(fn() => Cache::forget('countries_list'));
        static::updated(fn() => Cache::forget('countries_list'));
        static::deleted(fn() => Cache::forget('countries_list'));
    }
}
