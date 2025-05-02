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
         // First, create the table without partitioning and with composite primary key
         Schema::create('demands', function (Blueprint $table) {
            // Create columns but don't define a primary key yet
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('ulb_id');  // Foreign key but not constrained
            $table->unsignedBigInteger('tc_id')->nullable()->default(null);
            $table->unsignedBigInteger('ratepayer_id');  // Foreign key but not constrained
            $table->integer('opening_demand')->nullable();
            $table->integer('bill_month')->notNullable();
            $table->integer('bill_year')->notNullable();
            $table->integer('demand')->nullable();
            $table->integer('total_demand')->nullable();
            $table->integer('payment')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->string('deactivation_reason', 250)->nullable();
            $table->integer('vrno');
            $table->string('transaction_no', 45)->default('');
            $table->timestamps();
         });

         // Now define the correct primary key which includes bill_year
         DB::statement('ALTER TABLE demands ADD PRIMARY KEY (id, bill_year)');

         // Add auto_increment to id column after the primary key is defined
         DB::statement('ALTER TABLE demands MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
               
         // Creating the additional indices
         DB::statement('ALTER TABLE demands ADD UNIQUE INDEX unique_bill (bill_year, bill_month, ratepayer_id)');
         DB::statement('ALTER TABLE demands ADD UNIQUE INDEX id_partition_constraint (bill_year, id)');
         DB::statement('ALTER TABLE demands ADD INDEX Index_transaction_no (transaction_no)');
               
         // Adding regular indices for foreign key columns 
         DB::statement('ALTER TABLE demands ADD INDEX demands_ulb_id_index (ulb_id)');
         DB::statement('ALTER TABLE demands ADD INDEX demands_tc_id_index (tc_id)');
         DB::statement('ALTER TABLE demands ADD INDEX demands_ratepayer_id_index (ratepayer_id)');

         // Now add partitioning with raw SQL
         DB::statement('ALTER TABLE demands PARTITION BY RANGE (`bill_year`)
               (PARTITION p2022 VALUES LESS THAN (2022) ENGINE = InnoDB,
               PARTITION p2023 VALUES LESS THAN (2023) ENGINE = InnoDB,
               PARTITION p2024 VALUES LESS THAN (2024) ENGINE = InnoDB,
               PARTITION p2025 VALUES LESS THAN (2025) ENGINE = InnoDB,
               PARTITION p2026 VALUES LESS THAN (2026) ENGINE = InnoDB,
               PARTITION pmax VALUES LESS THAN MAXVALUE ENGINE = InnoDB)');

         // For current_demands table - we need to first add an index on id before setting it as AUTO_INCREMENT
         Schema::create('current_demands', function (Blueprint $table) {
            // Create columns but don't define a primary key yet - avoid autoIncrement
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('ulb_id');  // Foreign key but not constrained
            $table->unsignedBigInteger('tc_id')->nullable()->default(null);
            $table->unsignedBigInteger('ratepayer_id');  // Foreign key but not constrained
            $table->integer('opening_demand')->nullable();
            $table->integer('bill_month')->notNullable();
            $table->integer('bill_year')->notNullable();
            $table->integer('demand')->nullable();
            $table->integer('total_demand')->nullable();
            $table->integer('payment')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->string('deactivation_reason', 250)->nullable();
            $table->integer('vrno');
            $table->string('transaction_no', 45)->default('');
            $table->timestamps();
         });

         // First, add the primary key
         DB::statement('ALTER TABLE current_demands ADD PRIMARY KEY (id, bill_year)');

         // Now, add auto_increment to id column after the primary key is defined
         DB::statement('ALTER TABLE current_demands MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
               
         // Creating the additional indices
         DB::statement('ALTER TABLE current_demands ADD UNIQUE INDEX unique_bill (bill_year, bill_month, ratepayer_id)');
         DB::statement('ALTER TABLE current_demands ADD UNIQUE INDEX id_partition_constraint (bill_year, id)');
         DB::statement('ALTER TABLE current_demands ADD INDEX Index_transaction_no (transaction_no)');
               
         // Adding regular indices for foreign key columns 
         DB::statement('ALTER TABLE current_demands ADD INDEX demands_ulb_id_index (ulb_id)');
         DB::statement('ALTER TABLE current_demands ADD INDEX demands_tc_id_index (tc_id)');
         DB::statement('ALTER TABLE current_demands ADD INDEX demands_ratepayer_id_index (ratepayer_id)');

         // Now add partitioning with raw SQL
         DB::statement('ALTER TABLE current_demands PARTITION BY RANGE (`bill_year`)
               (PARTITION p2022 VALUES LESS THAN (2022) ENGINE = InnoDB,
               PARTITION p2023 VALUES LESS THAN (2023) ENGINE = InnoDB,
               PARTITION p2024 VALUES LESS THAN (2024) ENGINE = InnoDB,
               PARTITION p2025 VALUES LESS THAN (2025) ENGINE = InnoDB,
               PARTITION p2026 VALUES LESS THAN (2026) ENGINE = InnoDB,
               PARTITION pmax VALUES LESS THAN MAXVALUE ENGINE = InnoDB)');

         Schema::create('log_demands', function (Blueprint $table) {
               $table->id();
               $table->foreignId('ulb_id')->constrained('ulbs');
               $table->unsignedBigInteger('tc_id')->nullable();
               $table->unsignedBigInteger('ratepayer_id')->nullable();
               $table->integer('opening_demand')->nullable();
               $table->integer('bill_month')->nullable();
               $table->integer('bill_year')->nullable();
               $table->integer('demand')->nullable();
               $table->integer('total_demand')->nullable();
               $table->integer('payment')->nullable();
               $table->unsignedBigInteger('payment_id')->nullable();
               $table->boolean('is_active')->default(1);
               $table->string('deactivation_reason', 250)->nullable();
               $table->integer('vrno');
               $table->timestamps();
         });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demands');
    }
};
