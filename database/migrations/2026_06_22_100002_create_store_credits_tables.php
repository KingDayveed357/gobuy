<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store-credit wallet. `store_credits` holds the cached balance per user;
     * `store_credit_entries` is the append-only ledger — balance is always the
     * signed sum of entries, never edited in place. Money is integer kobo.
     */
    public function up(): void
    {
        Schema::create('store_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0); // kobo (cached sum of entries)
            $table->timestamps();
        });

        Schema::create('store_credit_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_credit_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');                 // signed kobo: +credit, -spend
            $table->string('type');                       // refund_credit | spend | expiry | admin_adjust
            $table->nullableMorphs('source');             // e.g. return_request, order
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('store_credit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_credit_entries');
        Schema::dropIfExists('store_credits');
    }
};
