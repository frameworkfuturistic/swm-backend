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
         $table->foreignId('entity_id')->constrained('entities')->nullable();
         $table->foreignId('cluster_id')->constrained('clusters')->nullable();
         $table->foreignId('payment_id')->constrained('payments')->nullable();
      
         $table->dateTime('event_time')->notNullable(); 
         $table->enum('event_type', ['payment', 'denial', 'no-show', 'deferred','other'])->default('payment');
         $table->string('remarks', 250)->nullable();
         $table->string('auto_remarks', 250)->nullable();
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
