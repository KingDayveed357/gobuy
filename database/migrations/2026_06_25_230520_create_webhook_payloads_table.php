<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_payloads', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index();
            $table->string('idempotency_key')->unique();
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_payloads');
    }
};
