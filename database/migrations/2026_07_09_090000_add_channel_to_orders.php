<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CO-0: the website becomes "just another sales channel". Every order records
 * the channel it came through (default 'web'); walk-in, phone, WhatsApp and
 * future POS channels are introduced by optional modules as plain string values
 * — never special-cased in the Commerce Core.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('channel', 32)->default('web')->after('payment_method')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('channel');
        });
    }
};
