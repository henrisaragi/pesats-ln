<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat cetak SATS-LN: pengaturan cetak (penandatangan, sisa tinggi tabel spesies) dan file
     * PDF yang dibekukan saat tombol Print diklik, supaya hasil cetak tidak berubah walau data
     * SATS-LN/master berubah setelahnya. Lihat claude/verifikasi-satsln.md.
     */
    public function up(): void
    {
        Schema::create('sats_ln_prints', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('sats_ln_id')->constrained('sats_ln')->cascadeOnDelete();
            $table->foreignId('penandatangan_id')->constrained('users');
            $table->foreignId('printed_by_id')->constrained('users');
            $table->decimal('sisa_tinggi', 5, 2);
            $table->string('pdf_path');
            $table->timestamps();

            $table->index(['sats_ln_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sats_ln_prints');
    }
};
