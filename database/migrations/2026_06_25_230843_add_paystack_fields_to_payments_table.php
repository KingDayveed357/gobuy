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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('authorization_code')->nullable()->after('status');
            $table->string('channel')->nullable()->after('authorization_code');
            $table->string('card_type')->nullable()->after('channel');
            $table->string('last4', 4)->nullable()->after('card_type');
            $table->string('bank')->nullable()->after('last4');
            $table->string('ip_address')->nullable()->after('bank');
            $table->integer('fees')->default(0)->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'authorization_code',
                'channel',
                'card_type',
                'last4',
                'bank',
                'ip_address',
                'fees',
            ]);
        });
    }
};
