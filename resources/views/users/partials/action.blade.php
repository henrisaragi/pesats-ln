@php
    $primaryRole = $user->roles->first()?->name;
@endphp
<div class="hs-dropdown relative inline-flex">
    <button aria-expanded="false" aria-haspopup="menu" aria-label="{{ __('Dropdown') }}"
            class="hs-dropdown-toggle btn size-7.5 bg-default-200 hover:bg-default-600 text-default-500"
            hs-dropdown-placement="bottom-end" type="button">
        <i class="size-4" data-lucide="ellipsis"></i>
    </button>
    <div class="hs-dropdown-menu" role="menu">
        @can('users.edit')
            <a class="flex items-center gap-1.5 py-1.5 font-medium px-3 text-default-500 hover:bg-default-150 rounded cursor-pointer"
               data-user-edit
               data-id="{{ $user->id }}"
               data-name="{{ $user->name }}"
               data-username="{{ $user->username }}"
               data-email="{{ $user->email }}"
               data-entity-id="{{ $user->entity_id }}"
               data-nip="{{ $user->nip }}"
               data-jabatan="{{ $user->jabatan }}"
               data-role="{{ $primaryRole }}"
               data-is-active="{{ $user->is_active ? 1 : 0 }}">
                <i class="size-3" data-lucide="edit"></i>
                {{ __('Ubah') }}
            </a>
        @endcan
        @can('users.delete')
            <a class="flex items-center gap-1.5 py-1.5 font-medium px-3 text-danger hover:bg-default-150 rounded cursor-pointer"
               data-user-delete
               data-id="{{ $user->id }}"
               data-name="{{ $user->name }}">
                <i class="size-3" data-lucide="trash-2"></i>
                {{ __('Hapus') }}
            </a>
        @endcan
    </div>
</div>
