<?php

declare(strict_types=1);

namespace App\Http\Controllers\Entitas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entitas\EntitasRequest;
use App\Models\Entitas\Entitas;
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

        // Region dropdowns are rendered via <x-province-select>/<x-city-select>/<x-district-select>/
        // <x-subdistrict-select> (resources/views/components/*-select.blade.php), which each load
        // only what they need (province: full ~38-row list; city/district/sub-district: only the
        // currently-selected row, rest fetched via AJAX as the user picks a parent) - no full-table
        // options need to be passed in from here.
        return view('master.entitas.index', [
            'entities' => $entities,
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
