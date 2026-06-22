<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Category-scoped templates of expected specification labels, e.g. a
        // "Safety Helmet" template suggesting "Standard", "Material", "Weight".
        Schema::create('spec_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->json('labels')->nullable(); // suggested labels for the admin form
            $table->timestamps();
        });

        // Actual key/value specifications stored against a product (relational,
        // ordered) — replaces any free-form JSON.
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spec_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('spec_templates');
    }
};
