<div class="flex items-center gap-2">
    @can('ports.update')
        <button aria-label="{{ __('Ubah') }}"
                class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                data-port-edit
                data-id="{{ $port->id }}"
                data-code="{{ $port->code }}"
                data-name="{{ $port->name }}"
                data-country-id="{{ $port->country_id }}"
                data-type="{{ $port->type }}"
                type="button">
            <i class="size-4" data-lucide="edit"></i>
        </button>
    @endcan
    @can('ports.delete')
        <button aria-label="{{ __('Hapus') }}"
                class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                data-port-delete
                data-id="{{ $port->id }}"
                data-name="{{ $port->name }}"
                type="button">
            <i class="size-4" data-lucide="trash-2"></i>
        </button>
    @endcan
</div>
