<?php

declare(strict_types=1);

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class TimezoneAwareDateTime implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        // Get user's timezone from config (set by middleware)
        $userTimezone = config('app.display_timezone', 'Africa/Lubumbashi');

        // Parse the value and convert to user's timezone
        return Carbon::parse($value)
            ->setTimezone($userTimezone)
            ->toDateTimeString();
    }

    /**
     * Prepare the given value for storage.
     * Store dates in UTC as recommended by Laravel.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return null;
        }

        // Convert to UTC for storage (Laravel best practice)
        return Carbon::parse($value)->utc();
    }
}
