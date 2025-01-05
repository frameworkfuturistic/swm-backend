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
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->nullable()->index('Index_eventtype');
            $table->string('razorpay_payment_id', 255)->nullable()->index('Index_razorpaypaymentid');
            $table->text('payload')->nullable();
            $table->timestamps();
        });

        //   Schema::create('current_payment_webhooks', function (Blueprint $table) {
        //       $table->id();
        //       $table->string('event_type', 50)->nullable()->index('Index_eventtype');
        //       $table->string('razorpay_payment_id', 255)->nullable()->index('Index_razorpaypaymentid');
        //       $table->text('payload')->nullable();
        //       $table->timestamps();
        //   });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};
