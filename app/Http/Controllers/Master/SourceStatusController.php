<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SourceStatusRequest;
use App\Models\SourceStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SourceStatusController extends Controller
{
    public function index(Request $request): View
    {
        $sourceStatuses = SourceStatus::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('description', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.source-statuses.index', [
            'sourceStatuses' => $sourceStatuses,
        ]);
    }

    public function store(SourceStatusRequest $request): RedirectResponse
    {
        SourceStatus::create($request->validated());

        return redirect()->route('master.source-statuses.index')->with('success', __('Status asal berhasil ditambahkan.'));
    }

    public function update(SourceStatusRequest $request, SourceStatus $sourceStatus): RedirectResponse
    {
        $sourceStatus->update($request->validated());

        return redirect()->route('master.source-statuses.index')->with('success', __('Status asal berhasil diperbarui.'));
    }

    public function destroy(SourceStatus $sourceStatus): RedirectResponse
    {
        $sourceStatus->delete();

        return redirect()->route('master.source-statuses.index')->with('success', __('Status asal berhasil dihapus.'));
    }
}
