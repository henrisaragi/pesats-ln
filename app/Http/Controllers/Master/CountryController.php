<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\CountryRequest;
use App\Models\Country;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(Request $request): View
    {
        $countries = Country::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.countries.index', [
            'countries' => $countries,
        ]);
    }

    public function store(CountryRequest $request): RedirectResponse
    {
        Country::create($request->validated());

        return redirect()->route('master.countries.index')->with('success', __('Negara berhasil ditambahkan.'));
    }

    public function update(CountryRequest $request, Country $country): RedirectResponse
    {
        $country->update($request->validated());

        return redirect()->route('master.countries.index')->with('success', __('Negara berhasil diperbarui.'));
    }

    public function destroy(Country $country): RedirectResponse
    {
        try {
            $country->delete();
        } catch (QueryException) {
            return redirect()->route('master.countries.index')
                ->with('error', __('Negara tidak dapat dihapus karena masih digunakan oleh data pelabuhan.'));
        }

        return redirect()->route('master.countries.index')->with('success', __('Negara berhasil dihapus.'));
    }
}
