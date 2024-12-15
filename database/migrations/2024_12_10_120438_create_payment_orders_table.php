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
            $table->foreignId('user_id')->constrained('users')->notNullable();
            // $table->unsignedBigInteger('payment_id')->nullable()->index('Index_paymentid');

            // Razorpay specific fields
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();

            // Order details
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('INR');

            // Status tracking
            $table->string('status')->default('pending');
            $table->string('payment_status')->nullable();
            $table->string('payment_method')->nullable();

            // Failure and refund details
            $table->text('failure_reason')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_status')->nullable();

            // Additional metadata
            $table->json('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('current_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->foreignId('user_id')->constrained('users')->notNullable();
            // $table->unsignedBigInteger('payment_id')->nullable()->index('Index_paymentid');

            // Razorpay specific fields
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();

            // Order details
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('INR');

            // Status tracking
            $table->string('status')->default('pending');
            $table->string('payment_status')->nullable();
            $table->string('payment_method')->nullable();

            // Failure and refund details
            $table->text('failure_reason')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_status')->nullable();

            // Additional metadata
            $table->json('notes')->nullable();

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
