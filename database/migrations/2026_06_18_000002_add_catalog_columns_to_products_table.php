<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->string('condition')->default('new')->after('description'); // new | used | refurbished

            // Logistics attributes (used by weight/volumetric delivery pricing later).
            $table->unsignedInteger('weight_g')->nullable()->after('condition');
            $table->unsignedInteger('length_mm')->nullable()->after('weight_g');
            $table->unsignedInteger('width_mm')->nullable()->after('length_mm');
            $table->unsignedInteger('height_mm')->nullable()->after('width_mm');

            // USD landed cost in cents — for margin tracking against the NGN sale price.
            $table->unsignedBigInteger('cost_price_usd')->nullable()->after('height_mm');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn([
                'brand_id', 'condition', 'weight_g',
                'length_mm', 'width_mm', 'height_mm', 'cost_price_usd',
            ]);
        });
    }
};
