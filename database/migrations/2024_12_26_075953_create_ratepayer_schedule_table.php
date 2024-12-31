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
        Schema::create('ratepayer_schedule', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->foreignId('ulb_id')->constrained('ulbs');
            $table->unsignedBigInteger('tc_id'); // Foreign key or reference id
            $table->unsignedBigInteger('ratepayer_id'); // Foreign key or reference id
            $table->date('schedule_date'); // Schedule date
            $table->timestamps(); // Created at and Updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratepayer_schedule');
    }
};
