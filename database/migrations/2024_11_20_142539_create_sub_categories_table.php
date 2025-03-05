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
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('subcategory_code', 3)->notNullable(); // String column with a max length of 50
            $table->string('sub_category', 100)->notNullable(); // String column with a max length of 50
            $table->integer('rate')->notNullable(); // String column with a max length of 50
            $table->unique(['sub_category', 'category_id'], 'Index_subcategory'); // Composite unique key with explicit name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_categories');
    }
};
