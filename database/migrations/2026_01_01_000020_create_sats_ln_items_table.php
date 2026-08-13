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
        Schema::create('sats_ln_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sats_ln_id');
            $table->unsignedBigInteger('kuota_nasional_id')->nullable();     // null utk Import/Re-Export
            $table->unsignedBigInteger('kuota_perusahaan_id')->nullable();   // null utk Import/Re-Export
            $table->unsignedBigInteger('species_id');
            $table->unsignedBigInteger('harga_patokan_id')->nullable();
            $table->decimal('qty', 15, 2);                       // kuantitas pada izin
            $table->decimal('realisasi_on_port', 15, 2)->default(0); // realisasi di pelabuhan
            $table->string('peb_or_pib')->nullable();            // nomor dok. PEB (ekspor) / PIB (impor)
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('sats_ln_id')->references('id')->on('sats_ln')->cascadeOnDelete();
            $table->foreign('kuota_nasional_id')->references('id')->on('kuota_nasional')->nullOnDelete();
            $table->foreign('kuota_perusahaan_id')->references('id')->on('kuota_perusahaan')->nullOnDelete();
            $table->foreign('species_id')->references('id')->on('species')->restrictOnDelete();
            $table->foreign('harga_patokan_id')->references('id')->on('harga_patokan')->nullOnDelete();
            $table->index('sats_ln_id');
            $table->index('species_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sats_ln_items');
    }
};
