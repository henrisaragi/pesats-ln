@extends('layouts.vertical', ['title' => __('Video Panduan')])

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => __('Panduan'), 'title' => __('Video Panduan')])

    <div class="card">
        <div class="card-header">
            <h6 class="card-title">{{ __('Daftar Video Panduan') }}</h6>
            @can('video-panduan.create')
                <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#video-create-modal" type="button">
                    <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Tambah Video') }}
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
                                <th class="px-3.5 py-3 text-start" scope="col">{{ __('Sumber') }}</th>
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
                                        @if ($item->file_path)
                                            {{ __('Upload') }}: <span class="text-default-500">{{ $item->original_filename }}</span>
                                        @else
                                            {{ $item->youtubeEmbedUrl() ? 'YouTube' : __('Link') }}
                                        @endif
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
                                            @include('panduan.partials.video-play-button', [
                                                'video' => $item,
                                                'class' => 'btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500',
                                                'label' => '',
                                                'viewerId' => 'video-panduan-viewer-modal',
                                            ])
                                            @can('video-panduan.update')
                                                <button aria-label="{{ __('Ubah') }}" class="btn size-7.5 bg-default-200 hover:bg-primary/10 hover:text-primary text-default-500"
                                                        data-hs-overlay="#video-edit-modal-{{ $item->id }}" type="button">
                                                    <i class="size-4" data-lucide="edit"></i>
                                                </button>
                                            @endcan
                                            @can('video-panduan.delete')
                                                <button aria-label="{{ __('Hapus') }}" class="btn size-7.5 bg-default-200 hover:bg-danger/10 hover:text-danger text-default-500"
                                                        data-hs-overlay="#video-delete-modal-{{ $item->id }}" type="button">
                                                    <i class="size-4" data-lucide="trash-2"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3.5 py-6 text-center text-default-500" colspan="5">{{ __('Belum ada video panduan.') }}</td>
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

    @can('video-panduan.create')
        @include('panduan.partials.form-modal', [
            'modalId' => 'video-create-modal',
            'title' => __('Tambah Video Panduan'),
            'action' => route('video-panduan.store'),
            'method' => 'POST',
            'fieldsView' => 'panduan.video-panduan.source-fields',
            'item' => null,
        ])
    @endcan

    @foreach ($items as $item)
        @can('video-panduan.update')
            @include('panduan.partials.form-modal', [
                'modalId' => 'video-edit-modal-'.$item->id,
                'title' => __('Ubah Video Panduan'),
                'action' => route('video-panduan.update', $item),
                'method' => 'PUT',
                'fieldsView' => 'panduan.video-panduan.source-fields',
                'item' => $item,
            ])
        @endcan
        @can('video-panduan.delete')
            @include('panduan.partials.delete-modal', [
                'modalId' => 'video-delete-modal-'.$item->id,
                'message' => __('Video ":judul" akan dihapus permanen.', ['judul' => $item->judul]),
                'action' => route('video-panduan.destroy', $item),
            ])
        @endcan
    @endforeach

    <x-document-viewer-modal id="video-panduan-viewer-modal"/>
@endsection
