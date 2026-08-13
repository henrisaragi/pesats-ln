<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Permission\PermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    private const PROTECTED_PERMISSIONS = [
        'roles.view',
        'roles.create',
        'roles.edit',
        'roles.delete',
        'permissions.view',
        'permissions.create',
        'permissions.edit',
        'permissions.delete',
    ];

    public function index(Request $request): View
    {
        $permissions = Permission::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('permissions.index', [
            'permissions' => $permissions,
        ]);
    }

    public function store(PermissionRequest $request): RedirectResponse
    {
        Permission::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        return redirect()->route('permissions.index')->with('success', __('Permission berhasil ditambahkan.'));
    }

    public function update(PermissionRequest $request, Permission $permission): RedirectResponse
    {
        $permission->update(['name' => $request->validated('name')]);

        return redirect()->route('permissions.index')->with('success', __('Permission berhasil diperbarui.'));
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        if (in_array($permission->name, self::PROTECTED_PERMISSIONS, true)) {
            return redirect()->route('permissions.index')
                ->with('error', __('Permission bawaan sistem tidak dapat dihapus.'));
        }

        $permission->delete();

        return redirect()->route('permissions.index')->with('success', __('Permission berhasil dihapus.'));
    }
}
