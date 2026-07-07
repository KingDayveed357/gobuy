<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records storefront merchandising telemetry — one row per impression or click
 * on a homepage section. Aggregated into CTR to close the merchandising loop
 * (R6: no way to tell whether a curated block actually performs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16); // impression | click
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['homepage_section_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_events');
    }
};
