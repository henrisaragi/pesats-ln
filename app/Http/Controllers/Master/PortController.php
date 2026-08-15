<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PortRequest;
use App\Models\Country;
use App\Models\Port;
use App\Services\Master\PortSeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PortController extends Controller
{
    private const TYPES = ['sea' => 'Laut', 'air' => 'Udara'];

    public function index(): View
    {
        return view('master.ports.index', [
            'countryOptions' => Country::query()->orderBy('name')->get(),
            'portTypes' => collect(self::TYPES)->map(fn ($label) => __($label))->all(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $ports = Port::query()
            ->with('country')
            ->when(
                $request->filled('country_id'),
                fn ($query) => $query->where('country_id', $request->integer('country_id'))
            );

        return DataTables::of($ports)
            ->addColumn('country_name', fn (Port $port) => $port->country?->name ?? '-')
            ->addColumn('type_label', fn (Port $port) => __(self::TYPES[$port->type] ?? $port->type))
            ->addColumn('action', fn (Port $port) => view('master.ports.partials.action', ['port' => $port])->render())
            ->rawColumns(['action'])
            ->make(true);
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

    public function sync(PortSeedService $service): RedirectResponse
    {
        $summary = $service->seed();

        return redirect()->route('master.ports.index')->with('success', __(
            'Data pelabuhan berhasil dimuat: :upserted diproses, :skipped dilewati (kode negara tidak dikenali).',
            ['upserted' => $summary['upserted'], 'skipped' => $summary['skipped']]
        ));
    }
}
