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
        Schema::create('entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('entity_type', ['perusahaan', 'penangkaran', 'perorangan', 'upt_ksda']);
            $table->string('name');
            $table->string('address')->nullable();
            $table->unsignedBigInteger('provinsi_id')->nullable();
            $table->unsignedBigInteger('kota_id')->nullable();
            $table->unsignedBigInteger('kecamatan_id')->nullable();
            $table->unsignedBigInteger('kelurahan_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('entity_type');
            $table->index('provinsi_id');
            $table->index('kota_id');
            $table->index('kecamatan_id');
            $table->index('kelurahan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
