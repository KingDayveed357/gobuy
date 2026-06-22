<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Tax (applied at checkout). Default rate is the centralised
            // config('gobuy.vat_rate'); kept literal here as the schema default.
            $table->boolean('is_vat_inclusive')->default(true);
            $table->boolean('is_tax_exempt')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(7.5);

            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
