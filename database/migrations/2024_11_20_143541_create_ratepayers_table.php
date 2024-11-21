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
         $table->foreignId('entity_id')->constrained('entities')->nullable();
         $table->foreignId('cluster_id')->constrained('clusters')->nullable();
         $table->bigInteger('last_payment_id')->unsigned();
         $table->bigInteger('last_transaction_id')->unsigned();
         $table->string('ratepayer_name', 50)->nullable();
         $table->string('consumer_no', 50)->nullable(); // `consumer_no` column as varchar(255), nullable 
         $table->dateTime('first_payment_date')->nullable(); // `first_payment_date` column as datetime, nullable 
         $table->dateTime('first_bill_date')->nullable(); // `first_bill_date` column as datetime, nullable 
         $table->integer('opening_demand')->nullable(); // `opening_demand` column as int(11), nullable 
         $table->integer('monthly_demand')->nullable(); // `monthly_demand` column as int(11), nullable 
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
