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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->foreignId('ratepayer_id')->constrained('ratepayers')->notNullable();

            $table->unsignedBigInteger('entity_id')->nullable()->default(null);
            $table->foreign('entity_id')->references('id')->on('entities');

            $table->unsignedBigInteger('cluster_id')->nullable()->default(null);
            $table->foreign('cluster_id')->references('id')->on('clusters');

            $table->foreignId('tc_id')->constrained('users')->notNullable();

            $table->unsignedBigInteger('tran_id')->nullable()->default(null);

            $table->unsignedBigInteger('payment_order_id')->nullable()->index('Index_PaymentOrderID');

            $table->dateTime('payment_date')->notNullable()->index('Index_paymentdate');
            $table->enum('payment_mode', ['CASH', 'CARD', 'UPI', 'CHEQUE', 'ONLINE'])->nullable()->index('Index_paymentmode');
            $table->enum('payment_status', ['PENDING', 'COMPLETED', 'FAILED', 'REFUNDED'])->default('PENDING')->index('Index_paymentstatus');
            $table->integer('amount')->notNullable();
            $table->boolean('payment_verified')->nullable()->index('Index_paymentverified');
            $table->boolean('refund_initiated')->nullable()->index('Index_refundinitiated');
            $table->boolean('refund_verified')->nullable()->index('Index_refundverified');
            $table->unsignedBigInteger('verified_by')->nullable()->default(null);
            $table->string('card_number', 25)->nullable();
            $table->string('upi_id', 100)->nullable();
            $table->string('cheque_number', 25)->nullable();
            $table->string('bank_name', 25)->nullable();
            $table->string('neft_id', 25)->nullable();
            $table->string('neft_date', 25)->nullable();
            $table->unsignedBigInteger('ratepayercheque_id')->nullable()->default(null);
            $table->boolean('is_canceled')->default(false); // Active status
            $table->integer('vrno');
            $table->timestamps();
        });

        //   Schema::create('current_payments', function (Blueprint $table) {
        //       $table->id();
        //       $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
        //       $table->foreignId('ratepayer_id')->constrained('ratepayers')->notNullable();

        //       $table->unsignedBigInteger('entity_id')->nullable()->default(null);
        //       $table->foreign('entity_id')->references('id')->on('entities');

        //       $table->unsignedBigInteger('cluster_id')->nullable()->default(null);
        //       $table->foreign('cluster_id')->references('id')->on('clusters');

        //       $table->foreignId('tc_id')->constrained('users')->notNullable();

        //       $table->unsignedBigInteger('tran_id')->nullable()->default(null);

        //       $table->unsignedBigInteger('payment_order_id')->nullable()->index('Index_PaymentOrderID');

        //       $table->dateTime('payment_date')->notNullable()->index('Index_paymentdate');
        //       $table->enum('payment_mode', ['CASH', 'CARD', 'UPI', 'CHEQUE', 'ONLINE'])->nullable()->index('Index_paymentmode');
        //       $table->enum('payment_status', ['PENDING', 'COMPLETED', 'FAILED', 'REFUNDED'])->default('PENDING')->index('Index_paymentstatus');
        //       $table->integer('amount')->notNullable();
        //       $table->boolean('payment_verified')->nullable()->index('Index_paymentverified');
        //       $table->boolean('refund_initiated')->nullable()->index('Index_refundinitiated');
        //       $table->boolean('refund_verified')->nullable()->index('Index_refundverified');
        //       $table->string('card_number', 25)->nullable();
        //       $table->string('upi_id', 100)->nullable();
        //       $table->string('cheque_number', 25)->nullable();
        //       $table->boolean('is_canceled')->default(false); // Active status
        //       $table->integer('vrno');
        //       $table->timestamps();
        //   });

        Schema::create('log_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->unsignedBigInteger('ratepayer_id')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable()->default(null);
            $table->unsignedBigInteger('cluster_id')->nullable()->default(null);
            $table->unsignedBigInteger('tc_id')->nullable();
            $table->unsignedBigInteger('tran_id')->nullable();
            $table->unsignedBigInteger('payment_order_id')->nullable();

            $table->dateTime('payment_date')->notNullable()->index('Index_paymentdate');
            $table->enum('payment_mode', ['CASH', 'CARD', 'UPI', 'CHEQUE', 'ONLINE'])->nullable()->index('Index_paymentmode');
            $table->enum('payment_status', ['PENDING', 'COMPLETED', 'FAILED', 'REFUNDED'])->default('PENDING')->index('Index_paymentstatus');
            $table->integer('amount')->notNullable();
            $table->boolean('payment_verified')->nullable()->index('Index_paymentverified');
            $table->boolean('refund_initiated')->nullable()->index('Index_refundinitiated');
            $table->boolean('refund_verified')->nullable()->index('Index_refundverified');
            $table->unsignedBigInteger('verified_by')->nullable()->default(null);
            $table->string('card_number', 25)->nullable();
            $table->string('upi_id', 100)->nullable();
            $table->string('cheque_number', 25)->nullable();
            $table->string('bank_name', 25)->nullable();
            $table->string('neft_id', 25)->nullable();
            $table->string('neft_date', 25)->nullable();
            $table->unsignedBigInteger('ratepayercheque_id')->nullable()->default(null);
            $table->boolean('is_canceled')->default(false); // Active status
            $table->integer('vrno');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
