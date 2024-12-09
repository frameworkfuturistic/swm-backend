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
        Schema::create('entities', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->foreignId('ward_id')->constrained('wards')->notNullable();
            $table->foreignId('cluster_id')->nullable()->constrained('clusters')->nullOnDelete();
            $table->foreignId('subcategory_id')->constrained('sub_categories');
            $table->foreignId('verifiedby_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('appliedtc_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('lastpayment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->string('holding_no', 255)->nullable();
            $table->string('entity_name', 255)->notNullable();
            $table->text('entity_address')->notNullable();
            $table->string('pincode', 6)->nullable();
            $table->string('mobile_no', 15)->nullable();
            $table->string('landmark', 100)->nullable();
            $table->string('whatsapp_no', 12)->nullable();

            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->date('inclusion_date')->nullable(); // Inclusion date
            $table->date('verification_date')->nullable(); // Verification date
            $table->decimal('opening_demand', 15, 2)->nullable(); // For financial values
            $table->decimal('monthly_demand', 10, 2)->notNullable(); // Monthly bill amount
            $table->boolean('is_active')->default(true); // Active status
            $table->boolean('is_verified')->default(false); // Active status
            $table->enum('usage_type', ['Residential', 'Commercial', 'Industrial', 'Institutional'])->default('Residential')->notNullable(); // Type of entity
            $table->enum('status', ['verified', 'pending', 'suspended', 'closed'])->default('pending')->notNullable();
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // deleted_at for soft deletes

            $table->index('entity_name');
            $table->index('appliedtc_id');
            $table->index('verifiedby_id');
            $table->index('landmark');
            $table->index('mobile_no');
            $table->index('whatsapp_no');
            $table->index('is_active');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
