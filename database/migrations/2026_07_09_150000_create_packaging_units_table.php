<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Packaging units — the ops.packaging module (CO-6). A variant is stocked in ONE
 * base unit; a packaging unit is a sellable multiple of it (a 12-bottle carton,
 * a 24-sachet pack) with its own barcode and price. Inventory is always counted
 * in base units — a packaging unit is only a lens over the same stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packaging_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('name'); // e.g. "Carton"
            $table->unsignedInteger('multiplier'); // base units per packaging unit
            $table->string('barcode', 64)->nullable();
            $table->string('sku', 64)->nullable();
            $table->unsignedBigInteger('retail_price')->nullable(); // kobo, Money cast; null = derive from base price
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_variant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_units');
    }
};
