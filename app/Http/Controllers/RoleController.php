<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Role\RoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const PROTECTED_ROLE = 'super-admin';

    public function index(Request $request): View
    {
        $roles = Role::query()
            ->with('permissions')
            ->withCount('permissions')
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $permissions = Permission::query()->orderBy('name')->get();

        return view('roles.index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $role = Role::create([
                'name' => $request->validated('name'),
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($this->resolvePermissions($request->validated('permissions') ?? []));
        });

        return redirect()->route('roles.index')->with('success', __('Role berhasil ditambahkan.'));
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        DB::transaction(function () use ($request, $role): void {
            $role->update(['name' => $request->validated('name')]);
            $role->syncPermissions($this->resolvePermissions($request->validated('permissions') ?? []));
        });

        return redirect()->route('roles.index')->with('success', __('Role berhasil diperbarui.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === self::PROTECTED_ROLE) {
            return redirect()->route('roles.index')->with('error', __('Role super-admin tidak dapat dihapus.'));
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', __('Role berhasil dihapus.'));
    }

    /**
     * @param  array<int, int|string>  $permissionIds
     * @return Collection<int, Permission>
     */
    private function resolvePermissions(array $permissionIds): Collection
    {
        return Permission::query()->whereIn('id', $permissionIds)->get();
    }
}
