<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerRequest;
use App\Models\Country;
use App\Models\Customer\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $viewer = Auth::user();
        $canViewAll = $viewer->can('customer.view-all');

        $customers = Customer::query()
            ->with(['country', 'entitas'])
            ->when(! $canViewAll, fn ($query) => $query->where('entity_id', $viewer->entity_id))
            ->orderBy('name')
            ->paginate(10);

        return view('customer.index', [
            'customers' => $customers,
            'canViewAll' => $canViewAll,
            'countryOptions' => Country::query()->orderBy('name')->get(),
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse|JsonResponse
    {
        $customer = Customer::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json($customer->only(['id', 'name', 'country_id']), 201);
        }

        return redirect()->route('customer.index')->with('success', __('Customer berhasil ditambahkan.'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorizeOwnEntity($customer);

        $customer->update($request->validated());

        return redirect()->route('customer.index')->with('success', __('Customer berhasil diperbarui.'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorizeOwnEntity($customer);

        $customer->delete();

        return redirect()->route('customer.index')->with('success', __('Customer berhasil dihapus.'));
    }

    /**
     * Data customer harus ketat per entitas: berbeda dari beberapa modul lain, permission
     * customer.view-all HANYA memberi akses lihat (audit lintas entitas) - tidak pernah
     * memberi izin ubah/hapus data customer milik entitas lain, siapa pun itu.
     */
    private function authorizeOwnEntity(Customer $customer): void
    {
        abort_unless(Auth::user()->entity_id === $customer->entity_id, 403);
    }
}
