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
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->unsignedBigInteger('payment_id')->nullable()->index('Index_paymentid');
            $table->string('razorpay_order_id', 255)->nullable()->index('Index_razorapyorderid');
            $table->string('gateway_name', 255)->nullable();
            $table->text('gateway_request_payload')->nullable();
            $table->text('gateway_response_payload')->nullable();
            $table->integer('amount')->nullable();
            $table->string('order_id', 255)->nullable()->index('Index_orderid');
            $table->string('gateway_payment_id', 255)->nullable()->index('Index_gatewaypaymentid');
            $table->string('gateway_signature', 255)->nullable();
            $table->enum('gateway_payment_status', ['CREATED', 'PAID', 'ATTEMPTED'])->default('CREATED');
            $table->string('gateway_status_code', 255)->nullable();
            $table->boolean('payment_pending')->nullable();
            $table->timestamps();
        });

        Schema::create('current_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->unsignedBigInteger('payment_id')->nullable()->index('Index_paymentid');
            $table->string('razorpay_order_id', 255)->nullable()->index('Index_razorapyorderid');
            $table->string('gateway_name', 255)->nullable();
            $table->text('gateway_request_payload')->nullable();
            $table->text('gateway_response_payload')->nullable();
            $table->integer('amount')->nullable();
            $table->string('order_id', 255)->nullable()->index('Index_orderid');
            $table->string('gateway_payment_id', 255)->nullable()->index('Index_gatewaypaymentid');
            $table->string('gateway_signature', 255)->nullable();
            $table->enum('gateway_payment_status', ['CREATED', 'PAID', 'ATTEMPTED'])->default('CREATED');
            $table->string('gateway_status_code', 255)->nullable();
            $table->boolean('payment_pending')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_orders');
    }
};
