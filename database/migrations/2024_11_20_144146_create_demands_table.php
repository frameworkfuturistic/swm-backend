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

            $table->unsignedBigInteger('tc_id')->nullable()->default(null);
            $table->foreign('tc_id')->references('id')->on('users');

            $table->foreignId('ratepayer_id')->constrained('ratepayers');
            $table->integer('opening_demand')->nullable();
            $table->integer('bill_month')->notNullable();
            $table->integer('bill_year')->notNullable();
            $table->integer('demand')->nullable();
            $table->integer('total_demand')->nullable();
            $table->integer('payment')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->dateTime('last_payment_date')->nullable();
            $table->boolean('is_active')->default(1);
            $table->string('deactivation_reason', 250)->nullable();
            $table->integer('vrno');
            $table->timestamps();
        });

        Schema::create('current_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs');

            $table->unsignedBigInteger('tc_id')->nullable()->default(null);
            $table->foreign('tc_id')->references('id')->on('users');

            $table->foreignId('ratepayer_id')->constrained('ratepayers');
            $table->integer('opening_demand')->nullable();
            $table->integer('bill_month')->notNullable();
            $table->integer('bill_year')->notNullable();
            $table->integer('demand')->nullable();
            $table->integer('total_demand')->nullable();
            $table->integer('payment')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->dateTime('last_payment_date')->nullable();
            $table->boolean('is_active')->default(1);
            $table->string('deactivation_reason', 250)->nullable();
            $table->integer('vrno');
            $table->timestamps();
        });

        Schema::create('log_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs');
            $table->unsignedBigInteger('tc_id')->nullable();
            $table->unsignedBigInteger('ratepayer_id')->nullable();
            $table->integer('opening_demand')->nullable();
            $table->integer('bill_month')->nullable();
            $table->integer('bill_year')->nullable();
            $table->integer('demand')->nullable();
            $table->integer('total_demand')->nullable();
            $table->integer('payment')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->dateTime('last_payment_date')->nullable();
            $table->boolean('is_active')->default(1);
            $table->string('deactivation_reason', 250)->nullable();
            $table->integer('vrno');
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
