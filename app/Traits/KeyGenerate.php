<?php

declare(strict_types=1);

namespace App\Traits;

use Keygen\Keygen;

/**
 * Trait KeyGenerate
 *
 * This trait provides a method to generate unique keys for models.
 */
trait KeyGenerate
{
    public static function generateCode(string $column = 'id', int $length = 15, ?string $prefix = '')
    {
        $key = self::generateID($length, $prefix);

        while (self::where($column, $key)->count() > 0) {
            $key = self::generateID($length, $prefix);
        }

        return $key;
    }

    private static function generateID(int $length, ?string $prefix = '')
    {
        return Keygen::length($length)->mutable('length', 'prefix')->numeric()->prefix($prefix, false)->generate();
    }
}
