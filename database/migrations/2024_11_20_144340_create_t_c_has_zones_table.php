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
        Schema::create('tc_has_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tc_id')->constrained('users');
            $table->foreignId('paymentzone_id')->constrained('payment_zones')->notNullable();
            $table->dateTime('allotment_date')->nullable(); // `first_payment_date` column as datetime, nullable
            $table->dateTime('deactivation_date')->nullable(); // `first_bill_date` column as datetime, nullable
            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tc_has_zones');
    }
};
