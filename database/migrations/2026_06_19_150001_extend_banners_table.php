<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Visual configuration.
            $table->string('layout')->default('hero')->after('placement');   // hero | split | grid
            $table->string('theme')->default('indigo')->after('layout');     // colour preset (used when no image)
            $table->string('text_theme')->default('light')->after('theme');  // light | dark
            $table->string('cta_variant')->default('light')->after('cta_label'); // button style
            $table->unsignedTinyInteger('overlay_opacity')->default(35)->after('text_theme'); // 0..100 over image
            $table->string('focal_point')->default('center')->after('overlay_opacity'); // CSS background-position

            // Scheduling.
            $table->timestamp('starts_at')->nullable()->after('is_active');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn([
                'layout', 'theme', 'text_theme', 'cta_variant',
                'overlay_opacity', 'focal_point', 'starts_at', 'ends_at',
            ]);
        });
    }
};
