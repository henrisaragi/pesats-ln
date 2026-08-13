<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\KomoditasRequest;
use App\Models\Komoditas;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KomoditasController extends Controller
{
    public function index(Request $request): View
    {
        $komoditas = Komoditas::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.komoditas.index', [
            'komoditas' => $komoditas,
        ]);
    }

    public function store(KomoditasRequest $request): RedirectResponse
    {
        Komoditas::create($request->validated());

        return redirect()->route('master.komoditas.index')->with('success', __('Komoditas berhasil ditambahkan.'));
    }

    public function update(KomoditasRequest $request, Komoditas $komoditas): RedirectResponse
    {
        $komoditas->update($request->validated());

        return redirect()->route('master.komoditas.index')->with('success', __('Komoditas berhasil diperbarui.'));
    }

    public function destroy(Komoditas $komoditas): RedirectResponse
    {
        try {
            $komoditas->delete();
        } catch (QueryException) {
            return redirect()->route('master.komoditas.index')
                ->with('error', __('Komoditas tidak dapat dihapus karena masih digunakan oleh data spesies.'));
        }

        return redirect()->route('master.komoditas.index')->with('success', __('Komoditas berhasil dihapus.'));
    }
}
