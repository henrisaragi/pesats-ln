<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\KelurahanRequest;
use App\Jobs\SyncInswSubdistrictsForDistrictJob;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelurahanController extends Controller
{
    public function byKecamatan(Kecamatan $kecamatan): JsonResponse
    {
        return response()->json(
            $kecamatan->kelurahan()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function index(Request $request): View
    {
        $kelurahan = Kelurahan::query()
            ->with('kecamatan.kota.provinsi')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.kelurahan.index', [
            'kelurahan' => $kelurahan,
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

    public function sync(): RedirectResponse
    {
        $districts = Kecamatan::all();

        if ($districts->isEmpty()) {
            return redirect()->route('master.kelurahan.index')
                ->with('error', __('Belum ada data Kecamatan. Sinkronkan Kecamatan terlebih dahulu.'));
        }

        foreach ($districts as $kecamatan) {
            SyncInswSubdistrictsForDistrictJob::dispatch($kecamatan);
        }

        return redirect()->route('master.kelurahan.index')->with('success', __(
            ':count tugas sinkronisasi kelurahan telah dijadwalkan di antrean. Pastikan `php artisan queue:work` berjalan agar prosesnya selesai.',
            ['count' => $districts->count()]
        ));
    }
}
