<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KomoditasEntitasStatus;
use App\Models\Entitas\Entitas;
use App\Models\KomoditasEntitas;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class KomoditasEntitasService
{
    private const MASA_BERLAKU_PERMISSION = 'komoditas-entitas.masa-berlaku';

    /**
     * Sebagian role sengaja dikecualikan dari seluruh aturan masa berlaku (lihat
     * claude/status-cek-task.md) - dicek lewat permission komoditas-entitas.masa-berlaku milik
     * SALAH SATU user entitas itu (permission Spatie melekat ke role, bukan ke entitas, jadi
     * dicek lewat user-nya - biasanya seragam per entitas karena role mengikuti entity_type).
     * Kalau tidak ada satu pun user entitas ini yang punya permission-nya, komoditas milik
     * entitas ini TIDAK PERNAH dianggap kedaluwarsa berapa pun tanggal_berakhir-nya.
     */
    public function entitasSubjectToMasaBerlaku(Entitas $entitas): bool
    {
        return User::where('entity_id', $entitas->id)
            ->permission(self::MASA_BERLAKU_PERMISSION)
            ->exists();
    }

    /**
     * Versi bulk dari entitasSubjectToMasaBerlaku() - satu query lewat whereHas, dipakai job
     * harian (MarkExpiredKomoditasEntitasCommand) supaya tidak query per-entitas satu-satu.
     *
     * @return Collection<int, int> id entitas yang tunduk aturan masa berlaku
     */
    public function subjectToMasaBerlakuEntitasIds(): Collection
    {
        return Entitas::query()
            ->whereHas('users', fn ($query) => $query->permission(self::MASA_BERLAKU_PERMISSION))
            ->pluck('id');
    }

    /**
     * Komoditas milik entitas yang statusnya masih Verified tapi tanggal_berakhir-nya sudah
     * dekat (default 3 bulan lagi) - dipakai alert setelah login. Entitas yang dikecualikan dari
     * masa berlaku selalu mengembalikan collection kosong (tidak pernah dianggap "mendekati
     * kedaluwarsa").
     *
     * @return Collection<int, KomoditasEntitas>
     */
    public function expiringWithin(Entitas $entitas, int $months = 3): Collection
    {
        if (! $this->entitasSubjectToMasaBerlaku($entitas)) {
            return collect();
        }

        $today = Carbon::today();

        return KomoditasEntitas::query()
            ->with('komoditas')
            ->where('entitas_id', $entitas->id)
            ->where('status', KomoditasEntitasStatus::Verified)
            ->whereBetween('tanggal_berakhir', [$today, $today->copy()->addMonths($months)])
            ->orderBy('tanggal_berakhir')
            ->get();
    }
}
