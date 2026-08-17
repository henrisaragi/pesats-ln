<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * otp_code_hash/otp_expires_at: kode OTP login (dihash, satu kode pending per user, diganti
     * tiap percobaan login baru). current_session_id/last_activity_at: penegak "satu sesi aktif"
     * dan idle timeout 1 jam - lihat claude/auth-task.md.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code_hash')->nullable()->after('remember_token');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code_hash');
            $table->string('current_session_id')->nullable()->after('otp_expires_at');
            $table->timestamp('last_activity_at')->nullable()->after('current_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_code_hash', 'otp_expires_at', 'current_session_id', 'last_activity_at']);
        });
    }
};
