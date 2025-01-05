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
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable()->index('Index_PaymentID');
            $table->string('razorpay_refund_id', 100)->nullable()->index('Index_razorpayrefundid');
            $table->integer('refund_amount')->nullable();
            $table->enum('refund_status', ['INITIATED', 'PROCESSED', 'FAILED'])->default('INITIATED')->index('Index_refundstatus');
            $table->string('refund_reason', 255)->nullable();
            $table->timestamps();
        });

        //   Schema::create('current_payment_refunds', function (Blueprint $table) {
        //       $table->id();
        //       $table->unsignedBigInteger('payment_id')->nullable()->index('Index_PaymentID');
        //       $table->string('razorpay_refund_id', 100)->nullable()->index('Index_razorpayrefundid');
        //       $table->integer('refund_amount')->nullable();
        //       $table->enum('refund_status', ['INITIATED', 'PROCESSED', 'FAILED'])->default('INITIATED')->index('Index_refundstatus');
        //       $table->string('refund_reason', 255)->nullable();
        //       $table->timestamps();
        //   });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
