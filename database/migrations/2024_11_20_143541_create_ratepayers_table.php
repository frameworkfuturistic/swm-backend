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
            $table->foreignId('ulb_id')->constrained('ulbs');
            $table->foreignId('ward_id')->constrained('wards')->notNullable();
            $table->foreignId('entity_id')->nullable()->constrained('entities')->nullOnDelete();
            $table->foreignId('cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
            $table->foreignId('paymentzone_id')->nullable()->constrained('payment_zones')->nullOnDelete();
            $table->bigInteger('last_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('subcategory_id')->constrained('sub_categories');
            $table->bigInteger('rate_id')->nullable()->constrained('rate_list')->nullOnDelete();
            $table->bigInteger('last_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('ratepayer_name', 50)->nullable();
            $table->string('ratepayer_address', 255)->nullable();
            $table->string('consumer_no', 50)->nullable(); // `consumer_no` column as varchar(255), nullable
            $table->string('holding_no', 50)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->string('mobile_no', 15)->nullable();
            $table->string('landmark', 100)->nullable();
            $table->string('whatsapp_no', 12)->nullable();
            $table->enum('usage_type', ['Residential', 'Commercial', 'Industrial', 'Institutional'])->default('Residential')->notNullable(); // Type of entity
            $table->enum('status', ['verified', 'pending', 'suspended', 'closed'])->default('pending')->notNullable();
            $table->integer('reputation')->notNullable()->default(1);
            $table->dateTime('bill_date')->nullable(); // `first_bill_date` column as datetime, nullable
            $table->integer('opening_demand')->nullable(); // `opening_demand` column as int(11), nullable
            $table->integer('monthly_demand')->nullable(); // `monthly_demand` column as int(11), nullable
            $table->boolean('is_active')->default(true); // Active status
            $table->integer('vrno');
            $table->timestamps();
        });

        Schema::create('log_ratepayers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ulb_id')->constrained('ulbs');
            $table->bigInteger('ward_id')->nullable();
            $table->bigInteger('entity_id')->nullable();
            $table->bigInteger('cluster_id')->nullable();
            $table->bigInteger('paymentzone_id')->nullable();
            $table->bigInteger('last_payment_id')->nullable();
            $table->bigInteger('subcategory_id')->nullable();
            $table->bigInteger('rate_id')->nullable();
            $table->bigInteger('last_transaction_id')->nullable();
            $table->string('ratepayer_name', 50)->nullable();
            $table->string('ratepayer_address', 255)->nullable();
            $table->string('consumer_no', 50)->nullable(); // `consumer_no` column as varchar(255), nullable
            $table->string('holding_no', 50)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->string('mobile_no', 15)->nullable();
            $table->string('landmark', 100)->nullable();
            $table->string('whatsapp_no', 12)->nullable();
            $table->enum('usage_type', ['Residential', 'Commercial', 'Industrial', 'Institutional'])->default('Residential')->notNullable(); // Type of entity
            $table->enum('status', ['verified', 'pending', 'suspended', 'closed'])->default('pending')->notNullable();
            $table->integer('reputation')->notNullable()->default(1);
            $table->dateTime('bill_date')->nullable(); // `first_bill_date` column as datetime, nullable
            $table->integer('opening_demand')->nullable(); // `opening_demand` column as int(11), nullable
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
