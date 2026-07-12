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
        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');                  // 'google', 'facebook', … (config-driven)
            $table->string('provider_id');               // the provider's stable account id
            $table->string('provider_email')->nullable();
            $table->string('avatar')->nullable();
            $table->text('token')->nullable();           // encrypted at rest (model cast)
            $table->text('refresh_token')->nullable();   // encrypted at rest (model cast)
            $table->timestamps();

            // One provider identity maps to exactly one row — prevents identity reuse.
            $table->unique(['provider', 'provider_id']);
            $table->index(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
