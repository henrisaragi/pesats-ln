<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Entitas\Entitas;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'entityOptions' => Entitas::orderBy('name')->get(),
            'roleOptions' => Role::orderBy('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $viewer = $request->user();

        $users = User::query()
            ->with(['entity', 'roles'])
            ->when(
                ! $viewer->can('users.view-all'),
                fn ($query) => $viewer->entity_id === null
                    ? $query->whereNull('entity_id')
                    : $query->where('entity_id', $viewer->entity_id)
            )
            ->when(
                $request->filled('entity_id'),
                fn ($query) => $query->where('entity_id', $request->integer('entity_id'))
            );

        return DataTables::of($users)
            ->addColumn('entity_name', fn (User $user) => $user->entity?->name ?? '-')
            ->addColumn('role_name', fn (User $user) => $user->roles->pluck('name')->join(', ') ?: '-')
            ->addColumn('status', fn (User $user) => $user->is_active
                ? '<span class="py-0.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-medium bg-success/10 text-success rounded">'.__('Aktif').'</span>'
                : '<span class="py-0.5 px-2.5 inline-flex items-center gap-x-1 text-xs font-medium bg-danger/10 text-danger rounded">'.__('Nonaktif').'</span>')
            ->editColumn('created_at', fn (User $user) => $user->created_at?->translatedFormat('d M Y'))
            ->addColumn('action', fn (User $user) => view('users.partials.action', ['user' => $user])->render())
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function checkUsername(Request $request): JsonResponse
    {
        $username = str_replace(' ', '', (string) $request->query('username', ''));

        if ($username === '') {
            return response()->json(['available' => true]);
        }

        $exists = User::query()
            ->where('username', $username)
            ->when(
                $request->filled('exclude'),
                fn ($query) => $query->where('id', '!=', $request->integer('exclude'))
            )
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? __('Username sudah digunakan.') : null,
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::create([
                ...$request->safe()->except(['role', 'password']),
                'password' => Hash::make($request->validated('password')),
            ]);

            $user->syncRoles([$request->validated('role')]);
        });

        return redirect()->route('users.index')->with('success', __('User berhasil ditambahkan.'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $data = $request->safe()->except(['role', 'password']);

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->validated('password'));
            }

            $user->update($data);
            $user->syncRoles([$request->validated('role')]);
        });

        return redirect()->route('users.index')->with('success', __('User berhasil diperbarui.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', __('Anda tidak dapat menghapus akun sendiri.'));
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', __('User berhasil dihapus.'));
    }
}
