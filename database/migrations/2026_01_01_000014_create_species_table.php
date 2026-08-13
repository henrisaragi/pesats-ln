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
        Schema::create('species', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('komoditas_id');
            $table->string('scientific_name');
            $table->string('common_name')->nullable();
            $table->string('local_name')->nullable();
            $table->enum('trade_status', ['I', 'II', 'III', 'Non Appendix']);
            $table->timestamps();

            $table->foreign('komoditas_id')->references('id')->on('komoditas')->restrictOnDelete();
            $table->index('komoditas_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('species');
    }
};
