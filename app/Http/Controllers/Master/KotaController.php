<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\KotaRequest;
use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KotaController extends Controller
{
    public function byProvinsi(Provinsi $provinsi): JsonResponse
    {
        return response()->json(
            $provinsi->kota()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function index(Request $request): View
    {
        $kota = Kota::query()
            ->with('provinsi')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.kota.index', [
            'kota' => $kota,
            'provinsiOptions' => Provinsi::query()->orderBy('name')->get(),
        ]);
    }

    public function store(KotaRequest $request): RedirectResponse
    {
        Kota::create($request->validated());

        return redirect()->route('master.kota.index')->with('success', __('Kota berhasil ditambahkan.'));
    }

    public function update(KotaRequest $request, Kota $kota): RedirectResponse
    {
        $kota->update($request->validated());

        return redirect()->route('master.kota.index')->with('success', __('Kota berhasil diperbarui.'));
    }

    public function destroy(Kota $kota): RedirectResponse
    {
        try {
            $kota->delete();
        } catch (QueryException) {
            return redirect()->route('master.kota.index')
                ->with('error', __('Kota tidak dapat dihapus karena masih digunakan oleh data kecamatan.'));
        }

        return redirect()->route('master.kota.index')->with('success', __('Kota berhasil dihapus.'));
    }
}
