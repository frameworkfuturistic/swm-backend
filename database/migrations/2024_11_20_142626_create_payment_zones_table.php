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
        Schema::create('payment_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs')->notNullable();
            $table->foreignId('ward_id')->constrained('wards')->notNullable();
            $table->string('payment_zone', 50)->notNullable(); // String column with a max length of 50
            $table->json('coordinates'); // Store coordinates as JSON
            $table->string('description', 250)->notNullable(); // String column with a max length of 50

            $table->integer('apartments')->nullable();
            $table->integer('buildings')->nullable();
            $table->integer('govt_buildings')->nullable();
            $table->integer('colonies')->nullable();
            $table->integer('other_buildings')->nullable();
            $table->integer('residential')->nullable();
            $table->integer('commercial')->nullable();
            $table->integer('industrial')->nullable();
            $table->integer('institutional')->nullable();
            $table->integer('monthly_demand')->nullable();
            $table->integer('yearly_demand')->nullable();

            $table->unique(['payment_zone', 'ulb_id'], 'Index_payment_zone'); // Composite unique key with explicit name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_zones');
    }
};
