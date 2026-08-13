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
        Schema::create('kuota_nasional', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('komoditas_id');
            $table->unsignedBigInteger('species_id');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('trade_code_id');
            $table->unsignedBigInteger('harga_patokan_id');
            $table->decimal('quota', 15, 2);
            $table->smallInteger('year');
            $table->timestamps();

            $table->foreign('komoditas_id')->references('id')->on('komoditas')->restrictOnDelete();
            $table->foreign('species_id')->references('id')->on('species')->restrictOnDelete();
            $table->foreign('source_id')->references('id')->on('source_statuses')->restrictOnDelete();
            $table->foreign('trade_code_id')->references('id')->on('trade_codes')->restrictOnDelete();
            $table->foreign('harga_patokan_id')->references('id')->on('harga_patokan')->restrictOnDelete();
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kuota_nasional');
    }
};
