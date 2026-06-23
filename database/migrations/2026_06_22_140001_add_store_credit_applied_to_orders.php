<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store credit applied to an order as a tender. The order total is
     * unchanged (goods + delivery + tax − coupon); this is how much of it the
     * wallet covers, so the gateway/POD/bank only collects `total − this`.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('store_credit_applied')->default(0)->after('refunded_total'); // kobo
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('store_credit_applied');
        });
    }
};
