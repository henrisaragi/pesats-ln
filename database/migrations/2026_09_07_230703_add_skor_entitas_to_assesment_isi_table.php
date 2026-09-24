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
        Schema::table('assesment_isi', function (Blueprint $table) {
            // Skor berdasarkan jawaban entitas SENDIRI (sebelum/tanpa koreksi verifikator),
            // dihitung saat submit - dipakai untuk perbandingan dengan skor_total (hasil resmi
            // setelah verifikasi) di daftar assesment-isi & verifikasi, lihat AssesmentSkorService.
            $table->decimal('skor_entitas', 8, 2)->nullable()->after('skor_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assesment_isi', function (Blueprint $table) {
            $table->dropColumn('skor_entitas');
        });
    }
};
