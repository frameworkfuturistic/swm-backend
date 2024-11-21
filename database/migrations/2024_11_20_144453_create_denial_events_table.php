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
      Schema::create('denial_events', function (Blueprint $table) {
         $table->id();
         $table->foreignId('entity_id')->constrained('entities');
         $table->foreignId('cluster_id')->constrained('clusters');
         $table->foreignId('tc_id')->constrained('users');
         $table->foreignId('denial_reason_id')->constrained('denial_reasons');
         $table->text('remarks')->nullable();
         $table->timestamps();
      });
   }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('denial_events');
    }
};
