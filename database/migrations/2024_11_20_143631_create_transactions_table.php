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
            // $table->id();
            // $table->unsignedBigInteger('ulb_id')->notNullable();
            // $table->unsignedBigInteger('tc_id')->notNullable();
            // $table->unsignedBigInteger('ratepayer_id')->notNullable();
            // $table->unsignedBigInteger('entity_id')->nullable();
            // $table->unsignedBigInteger('cluster_id')->nullable();
            // $table->unsignedBigInteger('payment_id')->nullable();
            // $table->unsignedBigInteger('denial_reason_id')->nullable();
            // $table->unsignedBigInteger('cancelledby_id')->nullable();
            // $table->unsignedBigInteger('verifiedby_id')->nullable();
            // $table->string('transaction_no', 50)->nullable();

            // $table->dateTime('event_time')->notNullable();
            // $table->date('cancellation_date')->nullable(); // Cancellation date
            // $table->date('verification_date')->nullable(); // Verification date
            // $table->date('schedule_date')->nullable(); // Verification date
            // $table->enum('event_type', ['PAYMENT', 'DENIAL', 'DOOR-CLOSED', 'DEFERRED', 'CHEQUE', 'OTHER'])->default('DEFERRED');
            // $table->string('remarks', 250)->nullable();
            // $table->string('auto_remarks', 250)->nullable();
            // $table->string('photo_path', 250)->nullable();
            // $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            // $table->decimal('latitude', 10, 7)->nullable();
            // $table->boolean('is_verified')->default(false); // Active status
            // $table->boolean('is_cancelled')->default(false); // Cancelled status
            // $table->integer('vrno');

            // $table->timestamps();
            $table->id();
            $table->unsignedBigInteger('ulb_id')->default(0);
            $table->unsignedBigInteger('tc_id')->default(0);
            $table->unsignedBigInteger('ratepayer_id')->default(0);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('cluster_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('denial_reason_id')->nullable();
            $table->unsignedBigInteger('cancelledby_id')->nullable();
            $table->unsignedBigInteger('verifiedby_id')->nullable();
            $table->string('transaction_no', 50)->nullable();
            $table->dateTime('event_time')->default(DB::raw("'0000-00-00 00:00:00'"));
            $table->date('cancellation_date')->nullable();
            $table->date('verification_date')->nullable();
            $table->date('schedule_date')->nullable();
            $table->enum('event_type', ['PAYMENT', 'DENIAL', 'DOOR-CLOSED', 'DEFERRED', 'CHEQUE', 'OTHER'])->default('DEFERRED');
            $table->string('remarks', 250)->nullable();
            $table->string('auto_remarks', 250)->nullable();
            $table->string('photo_path', 250)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->boolean('is_verified')->default(1);
            $table->boolean('is_cancelled')->default(1);
            $table->integer('vrno');
            $table->string('rec_receiptno', 45)->default('');
            $table->string('rec_ward', 45)->default('');
            $table->string('rec_consumerno', 45)->default('');
            $table->string('rec_name', 45)->default('');
            $table->string('rec_address', 45)->default('');
            $table->string('rec_category', 45)->default('');
            $table->string('rec_subcategory', 45)->default('');
            $table->string('rec_monthlycharge', 45)->default('');
            $table->string('rec_period', 45)->default('');
            $table->string('rec_amount', 45)->default('');
            $table->string('rec_paymentmode', 45)->default('');
            $table->string('rec_tcname', 45)->default('');
            $table->string('rec_tcmobile', 45)->default('');
            $table->string('rec_chequeno', 45)->default('');
            $table->string('rec_chequedate', 45)->default('');
            $table->string('rec_bankname', 45)->default('');
            $table->timestamps();

            // Indexes
            $table->index('ulb_id', 'Index_ulbid');
            $table->index('tc_id', 'Index_tcid');
            $table->index('ratepayer_id', 'Index_ratepayerid');
            $table->index('entity_id', 'Index_entityid');
            $table->index('cluster_id', 'Index_clusterid');
            $table->index('payment_id', 'Index_paymentid');
            $table->index('denial_reason_id', 'Index_denialreasonid');
            $table->index('transaction_no', 'Index_transactionno');
            $table->index('event_time', 'Index_eventtime');
            $table->index('event_type', 'Index_eventtype');
            $table->index('is_verified', 'Index_isverified');
            $table->index('is_cancelled', 'Index_iscancelled');
        });

        Schema::create('current_transactions', function (Blueprint $table) {
            // $table->id();
            // $table->unsignedBigInteger('ulb_id')->notNullable();
            // $table->unsignedBigInteger('tc_id')->notNullable();
            // $table->unsignedBigInteger('ratepayer_id')->notNullable();
            // $table->unsignedBigInteger('entity_id')->nullable();
            // $table->unsignedBigInteger('cluster_id')->nullable();
            // $table->unsignedBigInteger('payment_id')->nullable();
            // $table->unsignedBigInteger('denial_reason_id')->nullable();
            // $table->unsignedBigInteger('cancelledby_id')->nullable();
            // $table->unsignedBigInteger('verifiedby_id')->nullable();
            // $table->string('transaction_no', 50)->nullable();

            // $table->dateTime('event_time')->notNullable();
            // $table->date('cancellation_date')->nullable(); // Cancellation date
            // $table->date('verification_date')->nullable(); // Verification date
            // $table->date('schedule_date')->nullable(); // Verification date
            // $table->enum('event_type', ['PAYMENT', 'DENIAL', 'DOOR-CLOSED', 'DEFERRED', 'CHEQUE', 'OTHER'])->default('DEFERRED');
            // $table->string('remarks', 250)->nullable();
            // $table->string('auto_remarks', 250)->nullable();
            // $table->string('photo_path', 250)->nullable();
            // $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            // $table->decimal('latitude', 10, 7)->nullable();
            // $table->boolean('is_verified')->default(true); // Active status
            // $table->boolean('is_cancelled')->default(true); // Cancelled status
            // $table->integer('vrno');

            // $table->timestamps();

            $table->id();
            $table->unsignedBigInteger('ulb_id')->default(0);
            $table->unsignedBigInteger('tc_id')->default(0);
            $table->unsignedBigInteger('ratepayer_id')->default(0);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('cluster_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('denial_reason_id')->nullable();
            $table->unsignedBigInteger('cancelledby_id')->nullable();
            $table->unsignedBigInteger('verifiedby_id')->nullable();
            $table->string('transaction_no', 50)->nullable();
            $table->dateTime('event_time')->default(DB::raw("'0000-00-00 00:00:00'"));
            $table->date('cancellation_date')->nullable();
            $table->date('verification_date')->nullable();
            $table->date('schedule_date')->nullable();
            $table->enum('event_type', ['PAYMENT', 'DENIAL', 'DOOR-CLOSED', 'DEFERRED', 'CHEQUE', 'OTHER'])->default('DEFERRED');
            $table->string('remarks', 250)->nullable();
            $table->string('auto_remarks', 250)->nullable();
            $table->string('photo_path', 250)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->boolean('is_verified')->default(1);
            $table->boolean('is_cancelled')->default(1);
            $table->integer('vrno');
            $table->string('rec_receiptno', 45)->default('');
            $table->string('rec_ward', 45)->default('');
            $table->string('rec_consumerno', 45)->default('');
            $table->string('rec_name', 45)->default('');
            $table->string('rec_address', 45)->default('');
            $table->string('rec_category', 45)->default('');
            $table->string('rec_subcategory', 45)->default('');
            $table->string('rec_monthlycharge', 45)->default('');
            $table->string('rec_period', 45)->default('');
            $table->string('rec_amount', 45)->default('');
            $table->string('rec_paymentmode', 45)->default('');
            $table->string('rec_tcname', 45)->default('');
            $table->string('rec_tcmobile', 45)->default('');
            $table->string('rec_chequeno', 45)->default('');
            $table->string('rec_chequedate', 45)->default('');
            $table->string('rec_bankname', 45)->default('');
            $table->timestamps();

            // Indexes
            $table->index('ulb_id', 'Index_ulbid');
            $table->index('tc_id', 'Index_tcid');
            $table->index('ratepayer_id', 'Index_ratepayerid');
            $table->index('entity_id', 'Index_entityid');
            $table->index('cluster_id', 'Index_clusterid');
            $table->index('payment_id', 'Index_paymentid');
            $table->index('denial_reason_id', 'Index_denialreasonid');
            $table->index('transaction_no', 'Index_transactionno');
            $table->index('event_time', 'Index_eventtime');
            $table->index('event_type', 'Index_eventtype');
            $table->index('is_verified', 'Index_isverified');
            $table->index('is_cancelled', 'Index_iscancelled');
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
