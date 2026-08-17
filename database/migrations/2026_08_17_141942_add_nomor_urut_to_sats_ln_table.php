<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor urut SATS-LN dalam satu Permohonan - dulu cuma dihitung on-the-fly dari
     * $loop->iteration di Blade (berubah-ubah kalau ada baris di tengah yang dihapus/di-soft-
     * delete, atau kalau daftarnya difilter lewat fitur cari). Sekarang disimpan permanen saat
     * baris dibuat (lihat SatsLnController::store()), jadi begitu ditetapkan tidak pernah berubah
     * lagi - lihat claude/permohonan-task.md.
     */
    public function up(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->unsignedInteger('nomor_urut')->nullable()->after('permohonan_id');
        });

        $this->backfillNomorUrut();

        Schema::table('sats_ln', function (Blueprint $table) {
            $table->unsignedInteger('nomor_urut')->nullable(false)->change();
            $table->unique(['permohonan_id', 'nomor_urut']);
        });
    }

    public function down(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->dropUnique(['permohonan_id', 'nomor_urut']);
            $table->dropColumn('nomor_urut');
        });
    }

    /**
     * Backfill baris yang sudah ada berdasarkan urutan pembuatan (id ascending) per Permohonan -
     * termasuk baris yang sudah soft-deleted, supaya nomor tidak pernah dipakai ulang oleh baris
     * baru nanti (lihat withTrashed() di SatsLnController::store()). Postgres (produksi) memakai
     * window function langsung di SQL supaya satu query saja; driver lain (mis. SQLite di test
     * suite) memakai fallback per-baris karena sintaks UPDATE...FROM Postgres tidak portable.
     */
    private function backfillNomorUrut(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                UPDATE sats_ln
                SET nomor_urut = numbered.nomor_urut
                FROM (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY permohonan_id ORDER BY id) AS nomor_urut
                    FROM sats_ln
                ) AS numbered
                WHERE sats_ln.id = numbered.id
            SQL);

            return;
        }

        DB::table('sats_ln')->orderBy('id')->select(['id', 'permohonan_id'])
            ->get()
            ->groupBy('permohonan_id')
            ->each(function ($rows) {
                $nomorUrut = 1;

                foreach ($rows as $row) {
                    DB::table('sats_ln')->where('id', $row->id)->update(['nomor_urut' => $nomorUrut]);
                    $nomorUrut++;
                }
            });
    }
};
