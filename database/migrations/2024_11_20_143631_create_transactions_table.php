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
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->foreignId('tc_id')->constrained('users')->notNullable();
            $table->foreignId('ratepayer_id')->constrained('ratepayers')->notNullable();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('denial_reason_id')->nullable()->constrained('denial_reasons')->nullOnDelete();
            $table->foreignId('cancelledby_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verifiedby_id')->nullable()->constrained('users')->nullOnDelete();

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

        Schema::create('current_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->foreignId('tc_id')->constrained('users')->notNullable();
            $table->foreignId('ratepayer_id')->constrained('ratepayers')->notNullable();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('denial_reason_id')->nullable()->constrained('denial_reasons')->nullOnDelete();
            $table->foreignId('cancelledby_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verifiedby_id')->nullable()->constrained('users')->nullOnDelete();

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
