<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('placement')->default('home');          // where the section renders
            $table->string('type');                                 // product_rail | product_grid | category_grid | brand_rail | banner_row
            $table->string('source')->nullable();                  // featured | latest | best_sellers | category | brand
            $table->string('source_ref')->nullable();              // category/brand id, or banner placement
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->unsignedSmallInteger('item_limit')->default(8);
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['placement', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};
