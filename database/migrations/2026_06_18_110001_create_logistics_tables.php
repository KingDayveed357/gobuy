<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Fees in integer kobo.
            $table->unsignedBigInteger('base_fee')->default(0);
            $table->unsignedBigInteger('per_kg_fee')->default(0);
            $table->unsignedBigInteger('free_over_subtotal')->nullable(); // free delivery above this subtotal
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // A Nigerian state maps to exactly one delivery zone (relational, not JSON).
        Schema::create('delivery_zone_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->string('state');
            $table->timestamps();

            $table->unique('state');
        });

        Schema::create('pickup_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('phone')->nullable();
            $table->string('opening_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_locations');
        Schema::dropIfExists('delivery_zone_states');
        Schema::dropIfExists('delivery_zones');
    }
};
