<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\KuotaNasionalRequest;
use App\Models\Komoditas;
use App\Models\KuotaNasional;
use App\Models\SourceStatus;
use App\Models\TradeCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KuotaNasionalController extends Controller
{
    public function index(Request $request): View
    {
        $activeYear = $request->filled('year') ? $request->integer('year') : null;

        $kuotaNasional = KuotaNasional::query()
            ->with(['komoditas', 'species', 'source', 'tradeCode'])
            ->when($activeYear !== null, fn ($query) => $query->where('year', $activeYear))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search');

                $query->where(function ($sub) use ($term) {
                    $sub->whereHas('komoditas', fn ($k) => $k->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('species', fn ($s) => $s->where('scientific_name', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.kuota-nasional.index', [
            'kuotaNasional' => $kuotaNasional,
            'activeYear' => $activeYear,
            'komoditasOptions' => Komoditas::query()->orderBy('name')->get(),
            'sourceOptions' => SourceStatus::query()->orderBy('code')->get(),
            'tradeCodeOptions' => TradeCode::query()->orderBy('code')->get(),
        ]);
    }

    public function store(KuotaNasionalRequest $request): RedirectResponse
    {
        KuotaNasional::create($request->validated());

        return redirect()->route('master.kuota-nasional.index')->with('success', __('Kuota nasional berhasil ditambahkan.'));
    }

    public function update(KuotaNasionalRequest $request, KuotaNasional $kuotaNasional): RedirectResponse
    {
        $kuotaNasional->update($request->validated());

        return redirect()->route('master.kuota-nasional.index')->with('success', __('Kuota nasional berhasil diperbarui.'));
    }

    public function destroy(KuotaNasional $kuotaNasional): RedirectResponse
    {
        $kuotaNasional->delete();

        return redirect()->route('master.kuota-nasional.index')->with('success', __('Kuota nasional berhasil dihapus.'));
    }
}
