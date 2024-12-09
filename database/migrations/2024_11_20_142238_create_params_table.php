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
        Schema::create('params', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->string('param_name', 255)->nullable(); // `param_name` column with varchar(255) and NULL default
            $table->string('param_string', 255)->nullable(); // `param_string` column with varchar(255) and NULL default
            $table->integer('param_int')->nullable(); // `param_int` column with int(11) and NULL default
            $table->smallInteger('param_bool')->nullable(); // `param_bool` column with smallint(6) and NULL default
            $table->dateTime('param_date')->nullable(); // `param_date` column with datetime and NULL default
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('params');
    }
};
