<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Returns Management System. A return_request is the customer-facing
     * lifecycle wrapper; return_items are the per-line claims; return_events is
     * the append-only audit trail (mirrors the order status_histories pattern).
     */
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();              // RMA-260622-AB12
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('requested')->index();
            $table->string('reason_code');                      // high-level reason
            $table->text('customer_note')->nullable();
            $table->string('refund_destination')->default('store_credit'); // store_credit | original
            $table->unsignedBigInteger('refunded_total')->default(0);      // kobo settled on THIS return
            $table->unsignedTinyInteger('risk_score')->nullable();
            $table->json('risk_flags')->nullable();
            $table->boolean('auto_approved')->default(false);
            $table->string('return_shipping_payer')->default('customer');  // customer | merchant
            $table->timestamp('window_expires_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('settled_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');                  // requested
            $table->unsignedInteger('approved_quantity')->nullable();
            $table->unsignedBigInteger('unit_price_snapshot');    // kobo — what they PAID per unit
            $table->string('reason_code');
            $table->string('condition_reported')->nullable();     // unopened | opened | damaged
            $table->string('disposition')->nullable();            // restock | damaged | reject
            $table->boolean('restocked')->default(false);
            $table->text('inspection_note')->nullable();
            $table->timestamps();

            $table->index('return_request_id');
        });

        Schema::create('return_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('actor');                      // admin | user | null (system)
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->string('action');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();        // append-only, no updated_at

            $table->index('return_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_events');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_requests');
    }
};
