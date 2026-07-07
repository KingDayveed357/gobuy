<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Premium layout controls — all token-based with safe defaults so
            // existing banners render unchanged.
            $table->string('height')->default('md')->after('focal_point');            // sm | md | lg
            $table->string('content_position')->default('start')->after('height');    // start | center | end
            $table->string('title_size')->default('md')->after('content_position');   // sm | md | lg
            $table->string('cta_size')->default('md')->after('title_size');           // sm | md | lg
            $table->string('cta_radius')->default('pill')->after('cta_size');         // pill | rounded | square
            $table->string('ribbon')->nullable()->after('cta_radius');                // e.g. "-40%", "SALE"
            $table->timestamp('countdown_to')->nullable()->after('ribbon');           // deal countdown target
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn([
                'height', 'content_position', 'title_size',
                'cta_size', 'cta_radius', 'ribbon', 'countdown_to',
            ]);
        });
    }
};
