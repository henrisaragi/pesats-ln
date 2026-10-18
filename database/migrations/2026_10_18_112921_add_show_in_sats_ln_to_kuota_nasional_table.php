<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kuota_nasional', function (Blueprint $table) {
            $table->boolean('show_in_sats_ln')->default(true)->after('year');
        });
    }

    public function down(): void
    {
        Schema::table('kuota_nasional', function (Blueprint $table) {
            $table->dropColumn('show_in_sats_ln');
        });
    }
};
