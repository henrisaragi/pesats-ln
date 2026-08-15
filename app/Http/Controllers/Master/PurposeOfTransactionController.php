<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PurposeOfTransactionRequest;
use App\Models\PurposeOfTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurposeOfTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $purposeOfTransactions = PurposeOfTransaction::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.purpose-of-transactions.index', [
            'purposeOfTransactions' => $purposeOfTransactions,
        ]);
    }

    public function store(PurposeOfTransactionRequest $request): RedirectResponse
    {
        PurposeOfTransaction::create($request->validated());

        return redirect()->route('master.purpose-of-transactions.index')->with('success', __('Tujuan transaksi berhasil ditambahkan.'));
    }

    public function update(PurposeOfTransactionRequest $request, PurposeOfTransaction $purposeOfTransaction): RedirectResponse
    {
        $purposeOfTransaction->update($request->validated());

        return redirect()->route('master.purpose-of-transactions.index')->with('success', __('Tujuan transaksi berhasil diperbarui.'));
    }

    public function destroy(PurposeOfTransaction $purposeOfTransaction): RedirectResponse
    {
        $purposeOfTransaction->delete();

        return redirect()->route('master.purpose-of-transactions.index')->with('success', __('Tujuan transaksi berhasil dihapus.'));
    }
}
