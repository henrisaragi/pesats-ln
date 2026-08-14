<?php

declare(strict_types=1);

namespace App\Http\Controllers\Entitas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entitas\EntitasRequest;
use App\Models\Entitas\Entitas;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntitasController extends Controller
{
    public function index(Request $request): View
    {
        $entities = Entitas::query()
            ->with(['provinsi', 'kota', 'kecamatan', 'kelurahan'])
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.entitas.index', [
            'entities' => $entities,
            'provinsiOptions' => Provinsi::orderBy('name')->get(),
            'kotaOptions' => Kota::orderBy('name')->get(),
            'kecamatanOptions' => Kecamatan::orderBy('name')->get(),
            'kelurahanOptions' => Kelurahan::orderBy('name')->get(),
        ]);
    }

    public function store(EntitasRequest $request): RedirectResponse
    {
        Entitas::create($request->validated());

        return redirect()->route('master.entitas.index')->with('success', __('Entitas berhasil ditambahkan.'));
    }

    public function update(EntitasRequest $request, Entitas $entity): RedirectResponse
    {
        $entity->update($request->validated());

        return redirect()->route('master.entitas.index')->with('success', __('Entitas berhasil diperbarui.'));
    }

    public function destroy(Entitas $entity): RedirectResponse
    {
        $entity->delete();

        return redirect()->route('master.entitas.index')->with('success', __('Entitas berhasil dihapus.'));
    }
}
