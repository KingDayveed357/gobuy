<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit trail of every price change — critical for FX-driven repricing.
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('priceable'); // e.g. product_variant
            $table->string('field'); // retail_price | sale_price | wholesale_price
            $table->unsignedBigInteger('old_value')->nullable(); // kobo
            $table->unsignedBigInteger('new_value')->nullable(); // kobo
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['priceable_type', 'priceable_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
