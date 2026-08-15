<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Entitas\Entitas;
use App\Models\KuotaPerusahaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KuotaSayaController extends Controller
{
    public function index(Request $request): View
    {
        $entity = $this->ownEntityOrAbort();
        $activeYear = $request->filled('year') ? $request->integer('year') : now()->year;

        $kuotaPerusahaan = KuotaPerusahaan::query()
            ->with(['kuotaNasional.komoditas', 'kuotaNasional.species', 'kuotaNasional.source', 'kuotaNasional.tradeCode'])
            ->where('entity_id', $entity->id)
            ->where('year', $activeYear)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('kuota-saya.index', [
            'kuotaPerusahaan' => $kuotaPerusahaan,
            'activeYear' => $activeYear,
            'entity' => $entity,
        ]);
    }

    private function ownEntityOrAbort(): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless($entity !== null, 403);

        return $entity;
    }
}
