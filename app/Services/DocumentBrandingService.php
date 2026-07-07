<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * DocumentBrandingService
 *
 * Single source of truth for all branding data used across printed documents.
 * Reads from the store's editable settings (backed by the settings table +
 * cache) and falls back to sensible config defaults.
 *
 * Extend the STORE_KEYS constant in SettingsController and add new keys here
 * to automatically surface them in every printed document.
 */
class DocumentBrandingService
{
    private const CACHE_TTL = 300; // 5 minutes — branding rarely changes

    /**
     * @return array<string, mixed>
     */
    public function getBranding(): array
    {
        return Cache::remember('document.branding', self::CACHE_TTL, function () {
            $settings = Setting::all();

            return [
                'store_name'     => $settings['store_name']  ?? config('app.name', 'GoBuy'),
                'store_email'    => $settings['store_email']  ?? null,
                'store_phone'    => $settings['store_phone']  ?? null,
                'address'        => 'Port Harcourt, Rivers State, Nigeria',
                'tax_id'         => $settings['tax_id']       ?? null,
                'vat_number'     => $settings['vat_number']   ?? null,
                'website'        => config('app.url'),
                'currency'       => 'NGN',
                'logo_url'       => $settings['logo_url']     ?? null,
                'disclaimer'     => $settings['invoice_disclaimer']
                    ?? 'Thank you for your business. All prices are inclusive of applicable taxes unless stated otherwise.',
                'legal_notice'   => $settings['legal_notice']
                    ?? 'This document is computer-generated and is valid without a signature.',
            ];
        });
    }

    /**
     * Bust the branding cache. Call this after store settings are updated.
     */
    public function flush(): void
    {
        Cache::forget('document.branding');
    }
}
