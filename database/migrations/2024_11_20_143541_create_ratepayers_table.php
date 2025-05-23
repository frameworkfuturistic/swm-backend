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
        Schema::create('ratepayers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulb_id');
            $table->unsignedBigInteger('ward_id')->notNullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('cluster_id')->nullable();
            $table->unsignedBigInteger('ratepayer_id')->nullable();
            $table->unsignedBigInteger('paymentzone_id')->nullable();
            $table->unsignedBigInteger('last_payment_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->notNullable();
            $table->unsignedBigInteger('rate_id')->nullable();
            $table->unsignedBigInteger('last_transaction_id')->nullable();
            $table->string('ratepayer_name', 250)->nullable();
            $table->string('ratepayer_address', 255)->nullable();
            $table->string('consumer_no', 50)->nullable(); // `consumer_no` column as varchar(255), nullable
            $table->string('holding_no', 50)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->string('mobile_no', 250)->nullable();
            $table->string('landmark', 100)->nullable();
            $table->string('whatsapp_no', 12)->nullable();
            $table->enum('usage_type', ['Residential', 'Commercial', 'Industrial', 'Institutional'])->default('Residential')->notNullable(); // Type of entity
            $table->enum('status', ['verified', 'pending', 'suspended', 'closed'])->default('pending')->notNullable();
            $table->integer('reputation')->notNullable()->default(1);
            $table->integer('lastpayment_amt')->nullable();
            $table->dateTime('lastpayment_date')->nullable();
            $table->string('lastpayment_mode', 20)->nullable();
            $table->date('schedule_date')->nullable();
            $table->integer('current_demand')->nullable(); // `opening_demand` column as int(11), nullable
            $table->integer('monthly_demand')->nullable(); // `monthly_demand` column as int(11), nullable
            $table->boolean('is_active')->default(true); // Active status
            $table->integer('vrno');
            $table->timestamps();
        });

        Schema::create('log_ratepayers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulb_id');
            $table->unsignedBigInteger('ward_id')->notNullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('cluster_id')->nullable();
            $table->unsignedBigInteger('ratepayer_id')->nullable();
            $table->unsignedBigInteger('paymentzone_id')->nullable();
            $table->unsignedBigInteger('last_payment_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->notNullable();
            $table->unsignedBigInteger('rate_id')->nullable();
            $table->unsignedBigInteger('last_transaction_id')->nullable();

            $table->string('ratepayer_name', 250)->nullable();
            $table->string('ratepayer_address', 255)->nullable();
            $table->string('consumer_no', 50)->nullable(); // `consumer_no` column as varchar(255), nullable
            $table->string('holding_no', 50)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->string('mobile_no', 250)->nullable();
            $table->string('landmark', 100)->nullable();
            $table->string('whatsapp_no', 12)->nullable();
            $table->enum('usage_type', ['Residential', 'Commercial', 'Industrial', 'Institutional'])->default('Residential')->notNullable(); // Type of entity
            $table->enum('status', ['verified', 'pending', 'suspended', 'closed'])->default('pending')->notNullable();
            $table->integer('reputation')->notNullable()->default(1);
            $table->integer('lastpayment_amt')->nullable();
            $table->dateTime('lastpayment_date')->nullable();
            $table->string('lastpayment_mode', 20)->nullable();
            $table->date('schedule_date')->nullable();
            $table->integer('current_demand')->nullable(); // `opening_demand` column as int(11), nullable
            $table->integer('monthly_demand')->nullable(); // `monthly_demand` column as int(11), nullable
            $table->boolean('is_active')->default(true); // Active status
            $table->integer('vrno');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratepayers');
    }
};
