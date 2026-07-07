<?php

namespace App\Modules\Marketing\Enums;

enum SectionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }
}
