<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('sequence_generators', function (Blueprint $table) {
         $table->id();
         $table->string('type')->unique(); // transaction_no, consumer_no, etc.
         $table->string('prefix')->nullable(); // Optional prefix like TRX-, CONS-, etc.
         $table->integer('last_number')->default(0); // Last generated number
         $table->integer('increment_by')->default(1); // Increment step
         $table->integer('padding')->default(0); // How many zeros to pad (e.g., 5 = 00001)
         $table->string('suffix')->nullable(); // Optional suffix
         $table->timestamps();
     });
     
     // Insert some default sequences
     DB::table('sequence_generators')->insert([
         [
             'type' => 'transaction_no',
             'prefix' => 'TRX-',
             'last_number' => 0,
             'increment_by' => 1,
             'padding' => 8,
             'created_at' => now(),
             'updated_at' => now(),
         ],
         [
             'type' => 'consumer_no',
             'prefix' => 'CONS-',
             'last_number' => 0,
             'increment_by' => 1,
             'padding' => 6,
             'created_at' => now(),
             'updated_at' => now(),
         ],
     ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_generators');
    }
};
