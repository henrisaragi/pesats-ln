<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * satsln_number_issued sekarang null saat SATS-LN baru dibuat (bukan langsung digenerate) -
     * lihat claude/permohonan-task.md. Unique index tetap aman dengan banyak NULL (NULL tidak
     * dianggap sama dengan NULL lain oleh constraint UNIQUE di Postgres maupun SQLite).
     */
    public function up(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->string('satsln_number_issued')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->string('satsln_number_issued')->nullable(false)->change();
        });
    }
};
