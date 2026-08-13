<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\TradeCodeRequest;
use App\Models\TradeCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TradeCodeController extends Controller
{
    public function index(Request $request): View
    {
        $tradeCodes = TradeCode::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.trade-codes.index', [
            'tradeCodes' => $tradeCodes,
        ]);
    }

    public function store(TradeCodeRequest $request): RedirectResponse
    {
        TradeCode::create($request->validated());

        return redirect()->route('master.trade-codes.index')->with('success', __('Kode perdagangan berhasil ditambahkan.'));
    }

    public function update(TradeCodeRequest $request, TradeCode $tradeCode): RedirectResponse
    {
        $tradeCode->update($request->validated());

        return redirect()->route('master.trade-codes.index')->with('success', __('Kode perdagangan berhasil diperbarui.'));
    }

    public function destroy(TradeCode $tradeCode): RedirectResponse
    {
        $tradeCode->delete();

        return redirect()->route('master.trade-codes.index')->with('success', __('Kode perdagangan berhasil dihapus.'));
    }
}
