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
        Schema::create('kelurahan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kecamatan_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();

            $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->restrictOnDelete();
            $table->index('kecamatan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelurahan');
    }
};
