<?php

use App\Models\Setting;
use App\Support\Money;

if (! function_exists('setting')) {
    /**
     * Read an editable store setting, falling back to the given default (which
     * itself usually comes from config()). Safe before the table exists (e.g.
     * during early migrations) — returns the default on any failure.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            return Setting::get($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}

if (! function_exists('money')) {
    /**
     * Format any money value (a Money object, or a raw integer in kobo such as
     * a database aggregate) as a Naira string, e.g. "₦12,500.00".
     */
    function money(Money|int|float|string|null $value, bool $symbol = true): string
    {
        if ($value instanceof Money) {
            return $value->format($symbol);
        }

        if ($value === null) {
            return Money::zero()->format($symbol);
        }

        return Money::fromKobo((int) $value)->format($symbol);
    }
}

if (! function_exists('to_kobo')) {
    /**
     * Resolve any money value to its integer kobo amount (for JS payloads,
     * aggregates, and comparisons).
     */
    function to_kobo(Money|int|float|string|null $value): int
    {
        if ($value instanceof Money) {
            return $value->kobo;
        }

        return (int) ($value ?? 0);
    }
}
