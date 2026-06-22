<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name')->default('Default'); // e.g. "Large / Red"

            // Money is stored as integer kobo (1 Naira = 100 kobo).
            $table->unsignedBigInteger('retail_price');
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->unsignedBigInteger('wholesale_price')->nullable();

            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
