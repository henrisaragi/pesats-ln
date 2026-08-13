<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PortRequest;
use App\Models\Country;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortController extends Controller
{
    public function index(Request $request): View
    {
        $ports = Port::query()
            ->with('country')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.ports.index', [
            'ports' => $ports,
            'countryOptions' => Country::query()->orderBy('name')->get(),
        ]);
    }

    public function store(PortRequest $request): RedirectResponse
    {
        Port::create($request->validated());

        return redirect()->route('master.ports.index')->with('success', __('Pelabuhan berhasil ditambahkan.'));
    }

    public function update(PortRequest $request, Port $port): RedirectResponse
    {
        $port->update($request->validated());

        return redirect()->route('master.ports.index')->with('success', __('Pelabuhan berhasil diperbarui.'));
    }

    public function destroy(Port $port): RedirectResponse
    {
        $port->delete();

        return redirect()->route('master.ports.index')->with('success', __('Pelabuhan berhasil dihapus.'));
    }
}
