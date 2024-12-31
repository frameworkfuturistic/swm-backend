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
        Schema::create('temp_entities', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->unsignedBigInteger('zone_id')->notNullable();
            $table->unsignedBigInteger('tc_id')->notNullable();
            $table->unsignedBigInteger('subcategory_id')->notNullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('cluster_id')->nullable();

            $table->string('holding_no', 255)->nullable();
            $table->string('entity_name', 255)->notNullable();
            $table->text('entity_address')->notNullable();
            $table->string('pincode', 6)->nullable();
            $table->string('mobile_no', 15)->nullable();
            $table->string('landmark', 100)->nullable();
            $table->string('whatsapp_no', 12)->nullable();

            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->date('verification_date')->nullable(); // Verification date
            $table->boolean('is_verified')->default(false); // Active status
            $table->boolean('is_rejected')->default(false); // Active status
            $table->enum('usage_type', ['Residential', 'Commercial', 'Industrial', 'Institutional'])->default('Residential')->notNullable(); // Type of entity
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // deleted_at for soft deletes

            $table->index('entity_name');
            $table->index('is_rejected');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_entities');
    }
};
