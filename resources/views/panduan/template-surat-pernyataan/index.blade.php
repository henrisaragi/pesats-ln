@extends('layouts.vertical', ['title' => __('Template Surat Pernyataan')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Panduan'), 'title' => __('Template Surat Pernyataan')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Template Surat Pernyataan') }}</h6>
            @can('template-surat-pernyataan.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#template-create-modal" type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Template') }}
                </button>
            @endcan
        </div>
        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-default-200">
                            <thead class="bg-default-150">
                            <tr class="text-sm font-normal text-default-700 whitespace-nowrap">
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Judul') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('File') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Fitur') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Status') }}</th>
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Aksi') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($items as $item)
                                <tr class="text-default-800 font-normal text-sm whitespace-nowrap">
                                    <td class="px-3.5 py-3">
                                        <div>{{ $item->judul }}</div>
                                        @if ($item->keterangan)
                                            <div class="text-xs text-default-500 whitespace-normal">{{ $item->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3.5 py-3">
                                        @include('panduan.partials.template-view-button', [
                                            'template' => $item,
                                            'class' => 'text-primary hover:underline inline-flex items-center gap-1',
                                            'label' => $item->original_filename ?: __('Lihat'),
                                            'viewerId' => 'template-viewer-modal',
                                        ])
                                    </td>
                                    <td class="px-3.5 py-3">
                                        @foreach ($item->fitur as $fitur)
                                            <span class="inline-flex rounded-full px-2.5 py-0.75 border text-xs border-primary/30 bg-primary/10 text-primary">{{ $fitur->label() }}</span>
                                        @endforeach
                                    </td>
                                    <td class="px-3.5 py-3">
                                        @include('panduan.partials.status-badge', ['isActive' => $item->is_active])
                                    </td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            @can('template-surat-pernyataan.update')
                                                <button aria-label="{{ __('Ubah') }}" class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#template-edit-modal-{{ $item->id }}" type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('template-surat-pernyataan.delete')
                                                <button aria-label="{{ __('Hapus') }}" class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#template-delete-modal-{{ $item->id }}" type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="5">{{ __('Belum ada template surat pernyataan.') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <p class="text-default-500 text-sm">
                    {{ __('Menampilkan') }} <b>{{ $items->count() }}</b> {{ __('dari') }} <b>{{ $items->total() }}</b> {{ __('Data') }}
                </p>
                @include('layouts.partials/pagination', ['paginator' => $items])
            </div>
        </div>
    </div>

    @can('template-surat-pernyataan.create')
        @include('panduan.partials.form-modal', [
            'modalId' => 'template-create-modal',
            'title' => __('Tambah Template Surat Pernyataan'),
            'action' => route('template-surat-pernyataan.store'),
            'method' => 'POST',
            'fieldsView' => 'panduan.template-surat-pernyataan.file-fields',
            'item' => null,
        ])
    @endcan

    @foreach ($items as $item)
        @can('template-surat-pernyataan.update')
            @include('panduan.partials.form-modal', [
                'modalId' => 'template-edit-modal-'.$item->id,
                'title' => __('Ubah Template Surat Pernyataan'),
                'action' => route('template-surat-pernyataan.update', $item),
                'method' => 'PUT',
                'fieldsView' => 'panduan.template-surat-pernyataan.file-fields',
                'item' => $item,
            ])
        @endcan
        @can('template-surat-pernyataan.delete')
            @include('panduan.partials.delete-modal', [
                'modalId' => 'template-delete-modal-'.$item->id,
                'message' => __('Template ":judul" akan dihapus permanen beserta filenya.', ['judul' => $item->judul]),
                'action' => route('template-surat-pernyataan.destroy', $item),
            ])
        @endcan
    @endforeach

    <x-document-viewer-modal id="template-viewer-modal"/>
@endsection
