<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->after('id');
        });

        DB::table('sats_ln')->whereNull('public_id')->orderBy('id')->each(function (object $row): void {
            DB::table('sats_ln')->where('id', $row->id)->update(['public_id' => (string) Str::uuid()]);
        });

        Schema::table('sats_ln', function (Blueprint $table) {
            $table->uuid('public_id')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sats_ln', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};
