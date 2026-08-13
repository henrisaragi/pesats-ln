<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SpeciesRequest;
use App\Models\Komoditas;
use App\Models\Species;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpeciesController extends Controller
{
    public function index(Request $request): View
    {
        $species = Species::query()
            ->with('komoditas')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('scientific_name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.species.index', [
            'species' => $species,
            'komoditasOptions' => Komoditas::query()->orderBy('name')->get(),
        ]);
    }

    public function store(SpeciesRequest $request): RedirectResponse
    {
        Species::create($request->validated());

        return redirect()->route('master.species.index')->with('success', __('Spesies berhasil ditambahkan.'));
    }

    public function update(SpeciesRequest $request, Species $species): RedirectResponse
    {
        $species->update($request->validated());

        return redirect()->route('master.species.index')->with('success', __('Spesies berhasil diperbarui.'));
    }

    public function destroy(Species $species): RedirectResponse
    {
        $species->delete();

        return redirect()->route('master.species.index')->with('success', __('Spesies berhasil dihapus.'));
    }
}
