<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assesment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assesment\AssesmentRequest;
use App\Models\Assesment\Assesment;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AssesmentController extends Controller
{
    public function index(Request $request): View
    {
        $items = Assesment::query()
            ->withCount('pertanyaan')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('judul', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('assesment.index', [
            'items' => $items,
        ]);
    }

    public function store(AssesmentRequest $request): RedirectResponse
    {
        $assesment = Assesment::create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('assesment.pertanyaan.index', $assesment)
            ->with('success', __('Assesment berhasil dibuat, silakan tambahkan pertanyaan.'));
    }

    public function update(AssesmentRequest $request, Assesment $assesment): RedirectResponse
    {
        $assesment->update($request->validated());

        return redirect()->route('assesment.index')->with('success', __('Assesment berhasil diperbarui.'));
    }

    public function destroy(Assesment $assesment): RedirectResponse
    {
        try {
            $assesment->delete();
        } catch (QueryException) {
            return redirect()->route('assesment.index')
                ->with('error', __('Assesment tidak dapat dihapus karena masih memiliki pengajuan entitas.'));
        }

        return redirect()->route('assesment.index')->with('success', __('Assesment berhasil dihapus.'));
    }
}
