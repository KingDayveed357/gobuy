<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aggregated search terms power "trending searches". One row per
        // normalised term with a running hit counter (cheap upsert increment).
        Schema::create('search_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term')->unique();
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();

            $table->index('hits');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_terms');
    }
};
