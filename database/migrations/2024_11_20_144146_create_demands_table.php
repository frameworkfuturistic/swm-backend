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
        Schema::create('demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs');
            $table->foreignId('ratepayer_id')->constrained('ratepayers');
            $table->integer('opening_balance')->nullable();
            $table->integer('bill_month')->notNullable();
            $table->integer('bill_year')->notNullable();
            $table->integer('demand')->nullable();
            $table->integer('payment')->nullable();
            $table->timestamps();
        });

        Schema::create('current_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs');
            $table->foreignId('ratepayer_id')->constrained('ratepayers');
            $table->integer('bill_month')->notNullable();
            $table->integer('bill_year')->notNullable();
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
        Schema::dropIfExists('demands');
    }
};
