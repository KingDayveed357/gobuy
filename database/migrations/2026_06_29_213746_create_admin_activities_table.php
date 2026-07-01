<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase B — a single immutable, append-only audit log for the admin area.
     * Serves both the activity feed and login history (filter on `event`).
     */
    public function up(): void
    {
        Schema::create('admin_activities', function (Blueprint $table) {
            $table->id();
            // Actor — nullable so failed logins (unknown admin) are still recorded.
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('event')->index();                 // machine key e.g. auth.login, admin.payments.mark-paid
            $table->string('description')->nullable();         // human sentence
            $table->nullableMorphs('subject');                 // the thing acted on (order, payment, …)
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index(); // append-only — no updated_at

            $table->index(['admin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activities');
    }
};
