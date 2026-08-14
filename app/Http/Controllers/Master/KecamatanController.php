<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\KecamatanRequest;
use App\Models\Kecamatan;
use App\Models\Kota;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KecamatanController extends Controller
{
    public function byKota(Kota $kota): JsonResponse
    {
        return response()->json(
            $kota->kecamatan()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function index(Request $request): View
    {
        $kecamatan = Kecamatan::query()
            ->with('kota')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.kecamatan.index', [
            'kecamatan' => $kecamatan,
            'kotaOptions' => Kota::query()->orderBy('name')->get(),
        ]);
    }

    public function store(KecamatanRequest $request): RedirectResponse
    {
        Kecamatan::create($request->validated());

        return redirect()->route('master.kecamatan.index')->with('success', __('Kecamatan berhasil ditambahkan.'));
    }

    public function update(KecamatanRequest $request, Kecamatan $kecamatan): RedirectResponse
    {
        $kecamatan->update($request->validated());

        return redirect()->route('master.kecamatan.index')->with('success', __('Kecamatan berhasil diperbarui.'));
    }

    public function destroy(Kecamatan $kecamatan): RedirectResponse
    {
        try {
            $kecamatan->delete();
        } catch (QueryException) {
            return redirect()->route('master.kecamatan.index')
                ->with('error', __('Kecamatan tidak dapat dihapus karena masih digunakan oleh data kelurahan.'));
        }

        return redirect()->route('master.kecamatan.index')->with('success', __('Kecamatan berhasil dihapus.'));
    }
}
