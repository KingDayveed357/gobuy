<?php

namespace App\Documents\Abstracts;

use App\Documents\Contracts\DocumentInterface;

/**
 * BaseDocument
 *
 * Provides sensible defaults for all document types so concrete documents
 * only need to declare what makes them unique. The orientation, page size,
 * watermark, and back-URL all have reasonable defaults that subclasses can
 * override when needed (e.g. a landscape A4 for a wide report).
 */
abstract class BaseDocument implements DocumentInterface
{
    public function getOrientation(): string
    {
        return 'portrait';
    }

    public function getPageSize(): string
    {
        return 'A4';
    }

    public function showWatermark(): bool
    {
        return false;
    }

    public function getWatermarkText(): ?string
    {
        return null;
    }

    public function getBackUrl(): ?string
    {
        return null;
    }
}
