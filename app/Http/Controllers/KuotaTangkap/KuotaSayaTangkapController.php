<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaTangkap;

use App\Enums\KuotaPeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\KuotaTangkap\KuotaAllocation;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Perusahaan melihat alokasi kuota ekspor miliknya, hanya untuk periode yang sudah ditetapkan.
 */
class KuotaSayaTangkapController extends Controller
{
    public function index(Request $request): View
    {
        $entityId = $request->user()->entity_id;

        $allocations = KuotaAllocation::query()
            ->with(['period.provinsi', 'period.species', 'period.tradeCode', 'period.komoditas'])
            ->where('entity_id', $entityId ?? 0)
            ->whereHas('period', fn ($query) => $query->whereIn('status', [KuotaPeriodStatus::Ditetapkan->value, KuotaPeriodStatus::Ditutup->value]))
            ->latest('id')
            ->paginate(10);

        return view('kuota-tangkap.saya', ['allocations' => $allocations]);
    }
}
