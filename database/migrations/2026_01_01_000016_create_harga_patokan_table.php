<?php

declare(strict_types=1);

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
        Schema::create('harga_patokan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('trade_code_id');
            $table->unsignedBigInteger('species_id');
            $table->decimal('harga', 15, 2);
            $table->timestamps();

            $table->foreign('source_id')->references('id')->on('source_statuses')->restrictOnDelete();
            $table->foreign('trade_code_id')->references('id')->on('trade_codes')->restrictOnDelete();
            $table->foreign('species_id')->references('id')->on('species')->restrictOnDelete();
            $table->index(['source_id', 'trade_code_id', 'species_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harga_patokan');
    }
};
