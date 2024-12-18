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
        Schema::create('rate_list', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->string('rate_list', 255)->nullable(); // Rate list column
            $table->integer('amount')->nullable(); // Amount column
            $table->integer('vrno');
            $table->timestamps(); // Adds created_at and updated_at columns
        });

        Schema::create('log_rate_list', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->bigInteger('ulb_id')->constrained('ulbs')->notNullable();
            $table->string('rate_list', 255)->nullable(); // Rate list column
            $table->integer('amount')->nullable(); // Amount column
            $table->integer('vrno');
            $table->timestamps(); // Adds created_at and updated_at columns
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_list');
    }
};
