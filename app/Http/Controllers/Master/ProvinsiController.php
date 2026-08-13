<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ProvinsiRequest;
use App\Models\Provinsi;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProvinsiController extends Controller
{
    public function index(Request $request): View
    {
        $provinsi = Provinsi::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.provinsi.index', [
            'provinsi' => $provinsi,
        ]);
    }

    public function store(ProvinsiRequest $request): RedirectResponse
    {
        Provinsi::create($request->validated());

        return redirect()->route('master.provinsi.index')->with('success', __('Provinsi berhasil ditambahkan.'));
    }

    public function update(ProvinsiRequest $request, Provinsi $provinsi): RedirectResponse
    {
        $provinsi->update($request->validated());

        return redirect()->route('master.provinsi.index')->with('success', __('Provinsi berhasil diperbarui.'));
    }

    public function destroy(Provinsi $provinsi): RedirectResponse
    {
        try {
            $provinsi->delete();
        } catch (QueryException) {
            return redirect()->route('master.provinsi.index')
                ->with('error', __('Provinsi tidak dapat dihapus karena masih digunakan oleh data kota.'));
        }

        return redirect()->route('master.provinsi.index')->with('success', __('Provinsi berhasil dihapus.'));
    }
}
