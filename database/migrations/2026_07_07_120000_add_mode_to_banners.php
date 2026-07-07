<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MX1: image-first banners. `mode` selects the rendering contract —
 * 'composed' (HTML title/CTA/scrim over a background, the historical default)
 * or 'creative' (a finished, designer-produced campaign image IS the banner;
 * the frontend only adds responsive cropping, click-through and analytics).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table): void {
            $table->string('mode', 16)->default('composed')->after('layout');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table): void {
            $table->dropColumn('mode');
        });
    }
};
