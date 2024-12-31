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
        Schema::create('month_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ulb_id')->constrained('ulbs');
            $table->unsignedBigInteger('zone_id');
            $table->integer('month');
            $table->integer('year');
            $table->string('collection_type');
            $table->unsignedBigInteger('tc_id');
            $table->integer('collection_count')->default(0);
            $table->integer('denial_count')->default(0);
            $table->integer('noshow_count')->default(0);
            $table->integer('reschedule_count')->default(0);
            $table->decimal('collection_amount', 15, 2)->default(0);
            $table->integer('newentity_count')->default(0);
            $table->integer('newcluster_count')->default(0);
            $table->integer('cancellation_count')->default(0);
            $table->integer('apartment_count')->default(0);
            $table->decimal('apartment_demand', 15, 2)->default(0);
            $table->decimal('apartment_collection', 15, 2)->default(0);
            $table->integer('building_count')->default(0);
            $table->decimal('building_demand', 15, 2)->default(0);
            $table->decimal('building_collection', 15, 2)->default(0);
            $table->integer('govtbuilding_count')->default(0);
            $table->decimal('govtbuilding_demand', 15, 2)->default(0);
            $table->decimal('govtbuilding_collection', 15, 2)->default(0);
            $table->integer('colony_count')->default(0);
            $table->decimal('colony_demand', 15, 2)->default(0);
            $table->decimal('colony_collection', 15, 2)->default(0);
            $table->integer('other_count')->default(0);
            $table->decimal('other_demand', 15, 2)->default(0);
            $table->decimal('other_collection', 15, 2)->default(0);
            $table->integer('residential_count')->default(0);
            $table->decimal('residential_demand', 15, 2)->default(0);
            $table->decimal('residential_collection', 15, 2)->default(0);
            $table->integer('commercial_count')->default(0);
            $table->decimal('commercial_demand', 15, 2)->default(0);
            $table->decimal('commercial_collection', 15, 2)->default(0);
            $table->integer('industrial_count')->default(0);
            $table->decimal('industrial_demand', 15, 2)->default(0);
            $table->decimal('industrial_collection', 15, 2)->default(0);
            $table->integer('institutional_count')->default(0);
            $table->decimal('institutional_demand', 15, 2)->default(0);
            $table->decimal('institutional_collection', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('month_summary');
    }
};
