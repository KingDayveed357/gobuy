<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The reverse-logistics leg of a return — one per return request. The
     * tracking reference is generated in-house (mirrors the outbound waybill);
     * a carrier return-pickup API would populate carrier/label_path here.
     */
    public function up(): void
    {
        Schema::create('return_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('tracking_reference')->unique();
            $table->string('carrier')->nullable();
            $table->string('payer')->default('customer'); // customer | merchant (prepaid)
            $table->string('label_path')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_shipments');
    }
};
