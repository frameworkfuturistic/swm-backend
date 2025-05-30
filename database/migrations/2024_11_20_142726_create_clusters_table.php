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
        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulb_id')->notNullable(); // Correctly chaining notNullable
            $table->unsignedBigInteger('ward_id')->notNullable();
            $table->unsignedBigInteger('verifiedby_id')->nullable();
            $table->unsignedBigInteger('appliedtc_id')->nullable();
            $table->string('cluster_code', 20)->notNullable(); // Cluster name cannot be null
            $table->string('cluster_name', 160)->notNullable(); // Cluster name cannot be null
            $table->string('cluster_address', 255)->nullable();
            $table->string('pincode', 6)->nullable(); // Corrected nullable, pincode can be empty
            $table->string('landmark', 100)->nullable();
            $table->enum('cluster_type', ['Apartment', 'Building', 'Govt-Building', 'Colony', 'Other', 'None'])->default('None')->notNullable();
            $table->string('mobile_no', 12)->nullable();
            $table->string('whatsapp_no', 12)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->date('inclusion_date')->nullable();
            $table->date('verification_date')->nullable();
            $table->boolean('is_active')->default(true); // Active status
            $table->boolean('is_verified')->default(false); // Verified status
            $table->integer('vrno');
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // deleted_at for soft deletes

            // Index and unique constraints
            $table->unique(['cluster_code', 'ulb_id'], 'Index_cluster');
            $table->index('cluster_name');
            $table->index('appliedtc_id');
            $table->index('verifiedby_id');
            $table->index('landmark');
            $table->index('mobile_no');
            $table->index('whatsapp_no');
            $table->index('is_active');
            $table->index('is_verified');
        });

        Schema::create('log_clusters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulb_id')->notNullable(); // Correctly chaining notNullable
            $table->unsignedBigInteger('ward_id')->nullable();
            $table->unsignedBigInteger('verifiedby_id')->nullable();
            $table->unsignedBigInteger('appliedtc_id')->nullable();
            $table->string('cluster_code', 20)->notNullable(); // Cluster name cannot be null
            $table->string('cluster_name', 60)->notNullable(); // Cluster name cannot be null
            $table->string('cluster_address', 255)->nullable();
            $table->string('pincode', 6)->nullable(); // Corrected nullable, pincode can be empty
            $table->string('landmark', 100)->nullable();
            $table->enum('cluster_type', ['Apartment', 'Building', 'Govt Institution', 'Colony', 'Other', 'None'])->default('None')->notNullable();
            $table->string('mobile_no', 12)->nullable();
            $table->string('whatsapp_no', 12)->nullable();
            $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->date('inclusion_date')->nullable();
            $table->date('verification_date')->nullable();
            $table->boolean('is_active')->default(true); // Active status
            $table->boolean('is_verified')->default(false); // Verified status
            $table->integer('vrno');
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // deleted_at for soft deletes

            // Index and unique constraints
            $table->unique(['cluster_code', 'ulb_id'], 'Index_cluster');
            $table->index('cluster_name');
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
        Schema::dropIfExists('clusters');
    }
};
