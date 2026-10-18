<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * status_satsln: hasil verifikasi per SATS-LN (disetujui/on_hold/ditolak), data lama yang sudah
     * terverifikasi di-backfill "disetujui". disposisi_cetak_*: kapan & siapa yang mengirim
     * Permohonan ke Divisi Cetak (status Antrian Cetak).
     */
    public function up(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->string('status_satsln', 20)->nullable()->after('verified_id')->index();
        });

        DB::table('sats_ln')->whereNotNull('verified_id')->update(['status_satsln' => 'disetujui']);

        Schema::table('permohonan', function (Blueprint $table) {
            $table->timestamp('disposisi_cetak_at')->nullable()->after('verified_at');
            $table->foreignId('disposisi_cetak_by')->nullable()->after('disposisi_cetak_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disposisi_cetak_by');
            $table->dropColumn('disposisi_cetak_at');
        });

        Schema::table('sats_ln', function (Blueprint $table) {
            $table->dropIndex(['status_satsln']);
            $table->dropColumn('status_satsln');
        });
    }
};
