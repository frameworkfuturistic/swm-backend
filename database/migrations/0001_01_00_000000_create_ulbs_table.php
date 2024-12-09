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
        Schema::create('ulbs', function (Blueprint $table) {
            $table->id();
            $table->string('ulb_name', 80)->notNullable(); // String column with a max length of 250
            $table->unique(['ulb_name'], 'Index_ulb_name'); // Composite unique key with explicit name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ulbs');
    }
};
