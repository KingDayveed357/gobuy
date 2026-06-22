<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Castable;
use JsonSerializable;
use Stringable;

/**
 * Immutable money value object. The single representation of money across the
 * platform: stored as an integer number of kobo (1 Naira = 100 kobo) so there
 * is never floating-point arithmetic on money.
 *
 * Construct from kobo (internal/DB) with {@see fromKobo()} or from a Naira
 * figure entered by a human with {@see fromNaira()}. All arithmetic stays in
 * integer kobo and is therefore exact.
 */
final class Money implements Castable, JsonSerializable, Stringable
{
    private function __construct(public readonly int $kobo) {}

    public static function fromKobo(int $kobo): self
    {
        return new self($kobo);
    }

    public static function fromNaira(int|float|string $naira): self
    {
        return new self((int) round(((float) $naira) * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /** Naira as a float — for display/formatting only, never for maths. */
    public function toNaira(): float
    {
        return $this->kobo / 100;
    }

    public function isZero(): bool
    {
        return $this->kobo === 0;
    }

    public function isPositive(): bool
    {
        return $this->kobo > 0;
    }

    public function plus(self $other): self
    {
        return new self($this->kobo + $other->kobo);
    }

    public function minus(self $other): self
    {
        return new self($this->kobo - $other->kobo);
    }

    public function times(int $quantity): self
    {
        return new self($this->kobo * $quantity);
    }

    /** A percentage of this amount, rounded to the nearest kobo. */
    public function percentage(float $percent): self
    {
        return new self((int) round($this->kobo * $percent / 100));
    }

    public function equals(self $other): bool
    {
        return $this->kobo === $other->kobo;
    }

    public function lessThan(self $other): bool
    {
        return $this->kobo < $other->kobo;
    }

    public function min(self $other): self
    {
        return $this->kobo <= $other->kobo ? $this : $other;
    }

    /** Formatted Naira string, e.g. "₦12,500.00". */
    public function format(bool $symbol = true): string
    {
        $amount = number_format($this->kobo / 100, 2);

        return $symbol ? '₦'.$amount : $amount;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /** Serialised as kobo so JSON/AJAX payloads stay integer-exact. */
    public function jsonSerialize(): int
    {
        return $this->kobo;
    }

    public static function castUsing(array $arguments): string
    {
        return MoneyCast::class;
    }
}
