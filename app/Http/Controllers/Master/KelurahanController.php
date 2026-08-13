<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\KelurahanRequest;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelurahanController extends Controller
{
    public function index(Request $request): View
    {
        $kelurahan = Kelurahan::query()
            ->with('kecamatan')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.kelurahan.index', [
            'kelurahan' => $kelurahan,
            'kecamatanOptions' => Kecamatan::query()->orderBy('name')->get(),
        ]);
    }

    public function store(KelurahanRequest $request): RedirectResponse
    {
        Kelurahan::create($request->validated());

        return redirect()->route('master.kelurahan.index')->with('success', __('Kelurahan berhasil ditambahkan.'));
    }

    public function update(KelurahanRequest $request, Kelurahan $kelurahan): RedirectResponse
    {
        $kelurahan->update($request->validated());

        return redirect()->route('master.kelurahan.index')->with('success', __('Kelurahan berhasil diperbarui.'));
    }

    public function destroy(Kelurahan $kelurahan): RedirectResponse
    {
        $kelurahan->delete();

        return redirect()->route('master.kelurahan.index')->with('success', __('Kelurahan berhasil dihapus.'));
    }
}
