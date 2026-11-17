<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\KuotaPeriodStatus;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Models\User;

/**
 * Middleware permission:kuota.* di route adalah gerbang kasar; policy ini menambahkan aturan
 * status, cakupan wilayah, dan pemisahan pengaju/penyetuju (submitted_by ≠ penyetuju).
 */
class KuotaPeriodPolicy
{
    public function view(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.view') && $this->inScope($user, $period);
    }

    public function update(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.update') && $this->inScope($user, $period) && $period->status->isEditable();
    }

    public function delete(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.delete') && $this->inScope($user, $period) && $period->status === KuotaPeriodStatus::Draft;
    }

    public function calculate(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.calculate') && $this->inScope($user, $period) && $period->status->isEditable();
    }

    public function allocate(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.allocate') && $this->inScope($user, $period) && $period->status === KuotaPeriodStatus::Dihitung;
    }

    public function submit(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.submit') && $this->inScope($user, $period) && $period->status === KuotaPeriodStatus::Dihitung;
    }

    public function approve(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.approve')
            && $this->inScope($user, $period)
            && $period->status === KuotaPeriodStatus::Diajukan
            && $period->submitted_by !== $user->id;
    }

    public function return(User $user, KuotaPeriod $period): bool
    {
        return $this->approve($user, $period);
    }

    public function close(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.approve') && $this->inScope($user, $period) && $period->status === KuotaPeriodStatus::Ditetapkan;
    }

    public function realisasi(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.realisasi') && $this->inScope($user, $period) && $period->status === KuotaPeriodStatus::Ditetapkan;
    }

    public function export(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.export') && $this->inScope($user, $period);
    }

    /**
     * Perusahaan hanya melihat alokasinya sendiri, dan hanya setelah ditetapkan.
     */
    public function viewOwn(User $user, KuotaPeriod $period): bool
    {
        return $user->can('kuota.view-own')
            && $user->entity_id !== null
            && in_array($period->status, [KuotaPeriodStatus::Ditetapkan, KuotaPeriodStatus::Ditutup], true)
            && $period->allocations()->where('entity_id', $user->entity_id)->exists();
    }

    private function inScope(User $user, KuotaPeriod $period): bool
    {
        $provinsiId = KuotaPeriod::scopedProvinsiIdFor($user);

        return $provinsiId === null || $provinsiId === $period->provinsi_id;
    }
}
