<?php

declare(strict_types=1);

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

if (! function_exists('cleanAmount')) {

    // only for display purposes
    function cleanAmount(string $amount): int|float
    {
        if (str_contains($amount, '.')) {
            // Remove trailing zeros and dot if needed
            $trimmed = mb_rtrim(mb_rtrim($amount, '0'), '.');

            return (mb_strpos($trimmed, '.') === false) ? (int) $trimmed : (float) $trimmed;
        }

        return (int) $amount;
    }

}

if (! function_exists('get_parsed_phone_number')) {

    function get_parsed_phone_number(string $phone)
    {
        return mb_substr($phone, -9);
    }
}

if (! function_exists('log_and_debug')) {
    function log_and_debug($payload, $type = 'alert')
    {
        Log::$type(json_encode($payload));
    }
}

if (! function_exists('get_minor_amount')) {
    function get_minor_amount(int|float|Money $number)
    {
        if ($number instanceof Money) {
            return $number->getMinorAmount()->toInt();
        }

        return Money::of($number, 'USD')->getMinorAmount()->toInt();
    }
}

if (! function_exists('get_amount_from_minor')) {
    function get_amount_from_minor(int|float|string $number)
    {
        if (is_numeric($number)) {
            return Money::ofMinor($number, 'USD', roundingMode: RoundingMode::HALF_UP)->getAmount()->toFloat();
        }

        return $number;
    }
}

if (! function_exists('cast_recursive')) {

    function cast_recursive(mixed $value)
    {
        if (is_null($value)) {
            return $value;
        }

        if ($value instanceof Collection) {
            return $value->map(fn ($item) => cast_recursive($item));
        }

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = cast_recursive($val);
            }

            return $value;
        }
        if (is_object($value)) {
            $vars = get_object_vars($value);
            foreach ($vars as $key => $val) {
                $value->$key = cast_recursive($val);
            }

            return $value;
        }

        return match ($value) {
            'true' => true,
            'false' => false,
            default => (is_numeric($value)
                ? (str_contains((string) $value, '.') ? (float) $value : (int) $value)
                : $value),
        };
    }
}

if (! function_exists('format_amount')) {
    /**
     * Format an amount with FT suffix.
     *
     * @param  float  $amount  The amount to format
     * @return string The formatted amount with FT suffix (e.g., "1,500.00 FT")
     */
    function format_amount(float $amount): string
    {
        return number_format($amount, 2, '.', ',') . ' FT';
    }
}

if (! function_exists('round_amount')) {
    /**
     * Round an amount using Brick\Math for precise decimal handling.
     *
     * @param  int|float|string  $amount  The amount to round
     * @param  int  $precision  The number of decimal places (default: 2)
     * @return float The rounded amount
     */
    function round_amount(int|float|string $amount, int $precision = 2): float
    {
        if (! is_numeric($amount)) {
            return 0.0;
        }

        $scale = $precision;
        $roundingMode = RoundingMode::HALF_UP;

        try {
            $decimal = Brick\Math\BigDecimal::of((string) $amount);
            $rounded = $decimal->toScale($scale, $roundingMode);

            return (float) $rounded->toFloat();
        } catch (Exception $e) {
            // Fallback to native round if Brick\Math fails
            return (float) round((float) $amount, $precision);
        }
    }
}

if (! function_exists('carbonize')) {
    /**
     * Safely convert a value to a Carbon instance.
     *
     * If the value is already a Carbon instance, return it as-is.
     * If the value is a string or other format, parse it into a Carbon instance.
     * If the value is null, return null.
     *
     * @param  mixed  $value  The value to convert to Carbon
     */
    function carbonize($value): ?Carbon\Carbon
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof Carbon\Carbon) {
            return $value;
        }

        try {
            return Carbon\Carbon::parse($value);
        } catch (Exception $e) {
            // If parsing fails, return null
            return null;
        }
    }
}
