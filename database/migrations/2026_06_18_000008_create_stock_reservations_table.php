<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Soft holds placed when an item is added to a cart, so concurrent
        // shoppers cannot oversell. Released on checkout completion, removal,
        // or expiry. Available stock = stock − active (unexpired) reservations.
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('holder_key')->index(); // e.g. "cart:{id}"
            $table->unsignedInteger('quantity');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['product_variant_id', 'holder_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
