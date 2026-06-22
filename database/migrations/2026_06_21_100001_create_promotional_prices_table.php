<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Time-bound promotional price overlays. Non-destructive: the variant's own
     * retail/sale/wholesale prices are untouched — an active promo simply wins
     * in the pricing pipeline while its window is live.
     */
    public function up(): void
    {
        Schema::create('promotional_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable(); // campaign name, e.g. "Sallah Sale"
            $table->unsignedBigInteger('price'); // kobo
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotional_prices');
    }
};
