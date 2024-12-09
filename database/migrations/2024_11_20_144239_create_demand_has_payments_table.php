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
        Schema::create('demand_has_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('demands')->notNullable();
            $table->foreignId('payment_id')->constrained('payments')->notNullable();
            $table->integer('demand')->nullable();
            $table->integer('payment')->nullable();
            $table->timestamps();
        });

        Schema::create('current_demand_has_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demand_id')->constrained('current_demands')->notNullable();
            $table->foreignId('payment_id')->constrained('current_payments')->notNullable();
            $table->integer('demand')->nullable();
            $table->integer('payment')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demand_has_payments');
    }
};
