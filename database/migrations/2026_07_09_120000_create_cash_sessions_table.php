<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CO-3: the cash register / business day. A session is opened with a cash float
 * in the morning and closed at night by counting the drawer, the POS terminal
 * and transfers against what the day's walk-in sales say SHOULD be there — the
 * variance the notebook used to (mis)calculate by hand. All money in kobo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('opened_by_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->bigInteger('opening_float')->default(0); // cash in the drawer at open
            $table->timestamp('opened_at');

            $table->foreignId('closed_by_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('closed_at')->nullable()->index();

            // Physically counted at close.
            $table->bigInteger('counted_cash')->nullable();
            $table->bigInteger('counted_pos')->nullable();
            $table->bigInteger('counted_transfer')->nullable();

            // Snapshot of what the day's sales expected, frozen at close time.
            $table->bigInteger('expected_cash')->nullable();
            $table->bigInteger('expected_pos')->nullable();
            $table->bigInteger('expected_transfer')->nullable();

            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
