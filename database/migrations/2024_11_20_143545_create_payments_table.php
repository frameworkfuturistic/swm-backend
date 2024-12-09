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
            $table->foreignId('entity_id')->constrained('entities')->nullable();
            $table->foreignId('cluster_id')->constrained('clusters')->nullable();
            $table->foreignId('tc_id')->constrained('users')->notNullable();
            $table->dateTime('payment_date')->notNullable();
            $table->enum('payment_mode', ['cash', 'card', 'upi', 'cheque', 'online'])->default('cash');
            $table->integer('amount')->notNullable();
            $table->integer('tran_id')->constrained('transactions')->notNullable();
            $table->boolean('is_canceled')->default(false); // Active status
            $table->timestamps();
        });
        Schema::create('current_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->foreignId('ratepayer_id')->constrained('ratepayers')->notNullable();
            $table->foreignId('entity_id')->constrained('entities')->nullable();
            $table->foreignId('cluster_id')->constrained('clusters')->nullable();
            $table->foreignId('tc_id')->constrained('users')->notNullable();
            $table->dateTime('payment_date')->notNullable();
            $table->enum('payment_mode', ['cash', 'card', 'upi', 'cheque', 'online'])->default('cash');
            $table->integer('amount')->notNullable();
            $table->integer('tran_id')->constrained('transactions')->notNullable();
            $table->boolean('is_canceled')->default(false); // Active status
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
