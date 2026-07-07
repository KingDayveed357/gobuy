<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structured link destination {type, ref, label}. Lives alongside the
        // legacy raw-URL columns, which remain as a render-time fallback until
        // every banner/section is re-saved through the Link Picker.
        Schema::table('banners', function (Blueprint $table) {
            $table->json('cta_link')->nullable()->after('link_url');
        });

        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->json('cta_link')->nullable()->after('cta_url');
        });
    }

    public function down(): void
    {
        Schema::table('banners', fn (Blueprint $table) => $table->dropColumn('cta_link'));
        Schema::table('homepage_sections', fn (Blueprint $table) => $table->dropColumn('cta_link'));
    }
};
