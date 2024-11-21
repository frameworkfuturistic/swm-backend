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
         $table->foreignId('ulb_id')->constrained('ulbs')->notNullable(); // Correctly chaining notNullable
         $table->foreignId('zone_id')->constrained('payment_zones')->nullable(); 
         $table->foreignId('verifiedby_id')->constrained('users')->nullable();
         $table->foreignId('tc_id')->constrained('users')->nullable();
         $table->string('cluster_name', 60)->notNullable(); // Cluster name cannot be null
         $table->string('address', 255)->nullable();
         $table->string('landmark', 100)->nullable();
         $table->string('pincode', 6)->nullable(); // Corrected nullable, pincode can be empty
         $table->enum('cluster_type', ['Apartment', 'Building', 'Govt Institution', 'Colony', 'Other', 'None'])->default('None')->notNullable();
         $table->string('mobile', 12)->nullable();
         $table->string('whatsapp_no', 12)->nullable();
         $table->decimal('longitude', 10, 7)->nullable(); // Precision for GPS
         $table->decimal('latitude', 10, 7)->nullable();
         $table->dateTime('inclusion_date')->nullable();
         $table->dateTime('verification_date')->nullable();
         $table->boolean('is_active')->default(true); // Active status
         $table->boolean('is_verified')->default(false); // Verified status
         $table->timestamps(); // created_at and updated_at
         $table->softDeletes(); // deleted_at for soft deletes
     
         // Index and unique constraints
         $table->unique(['cluster_name', 'ulb_id'], 'Index_cluster');
         $table->index('landmark'); 
         $table->index('mobile'); 
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
