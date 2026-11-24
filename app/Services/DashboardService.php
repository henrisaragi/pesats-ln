<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AssesmentIsiStatus;
use App\Enums\KomoditasEntitasStatus;
use App\Models\Assesment\AssesmentIsi;
use App\Models\KomoditasEntitas;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ringkasan status untuk card dashboard. Verifikator melihat semua data, pemilik entitas hanya
 * data entitasnya sendiri, selain itu card disembunyikan (null) - cakupannya mengikuti halaman
 * yang dituju link card (KomoditasEntitasController / AssesmentIsiController beserta verifikasinya).
 * Hanya data yang diajukan (created_at) pada Tahun Aktif yang dihitung.
 */
class DashboardService
{
    /**
     * @return array{route: string, total: int, statuses: list<array{status: KomoditasEntitasStatus, total: int}>}|null
     */
    public function komoditasSummary(User $user, int $year): ?array
    {
        $query = KomoditasEntitas::query()->whereYear('created_at', $year);

        if ($user->can('komoditas-entitas.verify')) {
            $route = 'komoditas-entitas.verifikasi.index';
        } elseif ($user->can('komoditas-entitas.view') && in_array($user->entity?->entity_type, KomoditasEntitas::ALLOWED_ENTITY_TYPES, true)) {
            $route = 'komoditas-entitas.index';
            $query->where('entitas_id', $user->entity_id);
        } else {
            return null;
        }

        return ['route' => $route, ...$this->countByStatus($query, KomoditasEntitasStatus::cases())];
    }

    /**
     * @return array{route: string, total: int, statuses: list<array{status: AssesmentIsiStatus, total: int}>}|null
     */
    public function assesmentSummary(User $user, int $year): ?array
    {
        $query = AssesmentIsi::query()->whereYear('created_at', $year);

        if ($user->can('assesment-isi.verify')) {
            $route = 'assesment-isi.verifikasi.index';
        } elseif ($user->can('assesment-isi.view') && $user->entity_id !== null) {
            $route = 'assesment-isi.index';
            $query->where('entitas_id', $user->entity_id);
        } else {
            return null;
        }

        return ['route' => $route, ...$this->countByStatus($query, AssesmentIsiStatus::cases())];
    }

    /**
     * @template TStatus of BackedEnum
     *
     * @param  Builder<Model>  $query
     * @param  list<TStatus>  $cases
     * @return array{total: int, statuses: list<array{status: TStatus, total: int}>}
     */
    private function countByStatus(Builder $query, array $cases): array
    {
        $counts = $query->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = array_map(fn (BackedEnum $status): array => [
            'status' => $status,
            'total' => (int) ($counts[$status->value] ?? 0),
        ], $cases);

        return ['total' => array_sum(array_column($statuses, 'total')), 'statuses' => $statuses];
    }
}
