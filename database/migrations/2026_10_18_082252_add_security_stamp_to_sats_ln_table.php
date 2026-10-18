<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor Security Stamp SATS-LN, diisi verifikator lewat modal Verifikasi (wajib untuk
     * spesies Appendix I/II/III, kosong untuk Non Appendix) - lihat claude/verifikasi-satsln.md.
     */
    public function up(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->string('security_stamp')->nullable()->after('special_conditions');
        });
    }

    public function down(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->dropColumn('security_stamp');
        });
    }
};
