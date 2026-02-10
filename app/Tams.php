<?php

declare(strict_types=1);

namespace App;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class Tams
{
    /**
     * Tams version.
     *
     * @var string
     */
    private const VERSION = '1.0.0';

    /**
     * Get the current version of Tams.
     */
    public static function version(): string
    {
        return self::VERSION;
    }

    /**
     * Get the current API version, for extensions, of Tams.
     */
    public static function apiVersion(): string
    {
        return Str::beforeLast(self::VERSION, '.');
    }

    public static function getAvailableLocales()
    {
        return self::getAvailableLocaleCodes()->mapWithKeys(fn (string $file) => [
            $file => trans('messages.lang', [], $file),
        ]);
    }

    public static function getAvailableLocaleCodes()
    {
        return collect(File::directories(base_path('lang')))
            ->map(fn (string $path) => basename($path));
    }
}
