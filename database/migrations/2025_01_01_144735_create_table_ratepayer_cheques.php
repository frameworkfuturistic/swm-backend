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
        Schema::create('ratepayer_cheques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulb_id');
            $table->unsignedBigInteger('ratepayer_id');
            $table->unsignedBigInteger('tran_id');
            $table->string('cheque_no', 50);
            $table->date('cheque_date');
            $table->string('bank_name', 50);
            $table->decimal('amount', 10, 2);
            $table->date('realization_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_returned')->default(false);
            $table->string('return_reason', 255)->nullable();
            $table->timestamps();

            $table->foreign('ulb_id')->references('id')->on('ulbs')->onDelete('cascade');
            $table->foreign('ratepayer_id')->references('id')->on('ratepayers')->onDelete('cascade');
            // $table->foreign('tran_id')->references('id')->on('transactions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratepayer_cheques');
    }
};
