<?php

namespace App\Modules\Order\Enums;

use Illuminate\Support\Str;

/**
 * The channels an order can originate from. The Commerce Core only knows the
 * website; optional modules introduce further channels (walk-in, phone, social,
 * future POS) as plain string values. So this is a *vocabulary helper*, not a
 * DB cast — {@see labelFor()} names any channel string, known or module-added,
 * without ever throwing on an unrecognised value.
 */
enum SalesChannel: string
{
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Website',
        };
    }

    /** A human label for any channel string, including module-introduced ones. */
    public static function labelFor(?string $channel): string
    {
        $channel = $channel ?: self::Web->value;

        return self::tryFrom($channel)?->label() ?? Str::headline($channel);
    }
}
