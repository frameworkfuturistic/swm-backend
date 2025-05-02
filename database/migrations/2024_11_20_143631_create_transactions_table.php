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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulb_id')->notNullable();
            $table->unsignedBigInteger('tc_id')->notNullable();
            $table->unsignedBigInteger('ratepayer_id')->notNullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('cluster_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('denial_reason_id')->nullable();
            $table->unsignedBigInteger('cancelledby_id')->nullable();
            $table->unsignedBigInteger('verifiedby_id')->nullable();
            $table->string('transaction_no', 50)->nullable();

            $table->dateTime('event_time')->notNullable();
            $table->date('cancellation_date')->nullable(); // Cancellation date
            $table->date('verification_date')->nullable(); // Verification date
            $table->date('schedule_date')->nullable(); // Verification date
            $table->enum('event_type', ['PAYMENT', 'DENIAL', 'DOOR-CLOSED', 'DEFERRED', 'CHEQUE', 'OTHER'])->default('DEFERRED');
            $table->string('remarks', 250)->nullable();
            $table->string('auto_remarks', 250)->nullable();
            $table->string('photo_path', 250)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->boolean('is_verified')->default(false); // Active status
            $table->boolean('is_cancelled')->default(false); // Cancelled status
            $table->integer('vrno');

            $table->timestamps();
        });

        Schema::create('current_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulb_id')->notNullable();
            $table->unsignedBigInteger('tc_id')->notNullable();
            $table->unsignedBigInteger('ratepayer_id')->notNullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('cluster_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('denial_reason_id')->nullable();
            $table->unsignedBigInteger('cancelledby_id')->nullable();
            $table->unsignedBigInteger('verifiedby_id')->nullable();
            $table->string('transaction_no', 50)->nullable();

            $table->dateTime('event_time')->notNullable();
            $table->date('cancellation_date')->nullable(); // Cancellation date
            $table->date('verification_date')->nullable(); // Verification date
            $table->date('schedule_date')->nullable(); // Verification date
            $table->enum('event_type', ['PAYMENT', 'DENIAL', 'DOOR-CLOSED', 'DEFERRED', 'CHEQUE', 'OTHER'])->default('DEFERRED');
            $table->string('remarks', 250)->nullable();
            $table->string('auto_remarks', 250)->nullable();
            $table->string('photo_path', 250)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->boolean('is_verified')->default(true); // Active status
            $table->boolean('is_cancelled')->default(true); // Cancelled status
            $table->integer('vrno');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
