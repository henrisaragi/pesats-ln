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
        Schema::create('kuota_perusahaan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('entity_id');          // pemegang kuota
            $table->unsignedBigInteger('kuota_nasional_id');
            $table->decimal('quota', 15, 2);
            $table->smallInteger('year');
            $table->timestamps();

            $table->foreign('entity_id')->references('id')->on('entities')->restrictOnDelete();
            $table->foreign('kuota_nasional_id')->references('id')->on('kuota_nasional')->restrictOnDelete();
            $table->index('entity_id');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kuota_perusahaan');
    }
};
