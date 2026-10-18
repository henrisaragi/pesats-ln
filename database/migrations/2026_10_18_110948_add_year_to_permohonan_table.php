<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahun Aktif (tahun kuota nasional) saat Permohonan dibuat - dasar filter semua tabel
     * Permohonan. Data lama di-backfill dari tahun created_at.
     */
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->smallInteger('year')->nullable()->after('nomor_permohonan');
        });

        DB::table('permohonan')->select(['id', 'created_at'])->lazyById()->each(function (object $row): void {
            DB::table('permohonan')->where('id', $row->id)->update([
                'year' => $row->created_at !== null ? Carbon::parse($row->created_at)->year : now()->year,
            ]);
        });

        Schema::table('permohonan', function (Blueprint $table) {
            $table->smallInteger('year')->nullable(false)->change();
            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });
    }
};
