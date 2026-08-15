@extends('layouts.vertical', ['title' => 'Profil Entitas'])

@section('css')

@endsection

@php
    $entityTypeLabels = [
        'perusahaan' => __('Perusahaan'),
        'penangkaran' => __('Penangkaran'),
        'perorangan' => __('Perorangan'),
        'upt_ksda' => __('UPT KSDA'),
    ];
@endphp

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Entitas', 'title' => 'Profil Entitas'])

    <div class="mb-4">
        <a class="btn btn-sm bg-default-150 text-default-700 hover:bg-default-200" href="{{ route('master.entitas.index') }}">
            <i class="size-4 me-1" data-lucide="arrow-left"></i>{{ __('Kembali ke Daftar Entitas') }}
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h6 class="card-title">{{ $entity->name }}</h6>
                <p class="text-default-500 text-sm mt-1">
                    {{ $entityTypeLabels[$entity->entity_type] ?? $entity->entity_type }}
                </p>
            </div>
            @if ($profile)
                <div class="flex items-center gap-2">
                    @can('entity-profiles.update')
                        <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#entity-profile-edit-modal"
                                type="button">
                            <i class="size-4 me-1" data-lucide="edit"></i>{{ __('Ubah Profil') }}
                        </button>
                    @endcan
                    @can('entity-profiles.delete')
                        <button class="btn btn-sm bg-default-150 text-danger hover:bg-danger/10"
                                data-hs-overlay="#entity-profile-delete-modal" type="button">
                            <i class="size-4 me-1" data-lucide="trash-2"></i>{{ __('Hapus Profil') }}
                        </button>
                    @endcan
                </div>
            @endif
        </div>

        <div class="p-4">
            @if ($profile)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-default-200">
                        <tbody class="divide-y divide-default-200">
                        @foreach ($fields as $field => $meta)
                            <tr>
                                <td class="px-3.5 py-3 text-sm font-medium text-default-700 w-1/3">{{ __($meta['label']) }}</td>
                                <td class="px-3.5 py-3 text-sm text-default-800">{{ $profile->{$field} }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10">
                    <p class="text-default-500 text-sm mb-4">
                        {{ __('Entitas ini belum memiliki profil.') }}
                    </p>
                    @can('entity-profiles.create')
                        <button class="btn btn-sm bg-primary text-white" data-hs-overlay="#entity-profile-create-modal"
                                type="button">
                            <i class="size-4 me-1" data-lucide="plus"></i>{{ __('Buat Profil') }}
                        </button>
                    @endcan
                </div>
            @endif
        </div>
    </div>

    @can('entity-profiles.create')
        <div aria-labelledby="entity-profile-create-modal-label" id="entity-profile-create-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="entity-profile-create-modal-label">
                            {{ __('Buat Profil') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#entity-profile-create-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form action="{{ route('master.entitas.profile.store', $entity) }}" method="POST">
                        @csrf
                        <input name="_modal" type="hidden" value="entity-profile-create-modal">
                        <div class="p-4 overflow-y-auto">
                            <div class="flex flex-col gap-y-4">
                                @foreach ($fields as $field => $meta)
                                    <div>
                                        <label class="inline-block mb-2 text-sm font-medium"
                                               for="entity-profile-create-{{ $field }}">
                                            {{ __($meta['label']) }}
                                        </label>
                                        <input class="form-input" id="entity-profile-create-{{ $field }}" name="{{ $field }}"
                                               type="text"
                                               value="{{ old('_modal') === 'entity-profile-create-modal' ? old($field) : '' }}"/>
                                        @if (old('_modal') === 'entity-profile-create-modal' && $errors->has($field))
                                            <p class="mt-1 text-xs text-danger">{{ $errors->first($field) }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#entity-profile-create-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    @if ($profile)
        @can('entity-profiles.update')
            <div aria-labelledby="entity-profile-edit-modal-label" id="entity-profile-edit-modal"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                        <div class="card-header">
                            <h3 class="font-bold text-default-800 text-base" id="entity-profile-edit-modal-label">
                                {{ __('Ubah Profil') }}
                            </h3>
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#entity-profile-edit-modal" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <form action="{{ route('master.entitas.profile.update', $entity) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input name="_modal" type="hidden" value="entity-profile-edit-modal">
                            <div class="p-4 overflow-y-auto">
                                <div class="flex flex-col gap-y-4">
                                    @foreach ($fields as $field => $meta)
                                        <div>
                                            <label class="inline-block mb-2 text-sm font-medium"
                                                   for="entity-profile-edit-{{ $field }}">
                                                {{ __($meta['label']) }}
                                            </label>
                                            <input class="form-input" id="entity-profile-edit-{{ $field }}" name="{{ $field }}"
                                                   type="text"
                                                   value="{{ old('_modal') === 'entity-profile-edit-modal' ? old($field) : $profile->{$field} }}"/>
                                            @if (old('_modal') === 'entity-profile-edit-modal' && $errors->has($field))
                                                <p class="mt-1 text-xs text-danger">{{ $errors->first($field) }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                                <button class="btn bg-default-150" data-hs-overlay="#entity-profile-edit-modal" type="button">
                                    {{ __('Batal') }}
                                </button>
                                <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan

        @can('entity-profiles.delete')
            <div aria-labelledby="entity-profile-delete-modal-label" id="entity-profile-delete-modal"
                 class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
                 role="dialog" tabindex="-1">
                <div
                    class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 md:w-sm m-3 mx-auto min-h-[calc(100%-56px)] flex items-center">
                    <div
                        class="w-full flex flex-col card shadow-2xs border border-default-200 rounded-xl pointer-events-auto px-6 py-8 relative">
                        <div class="absolute top-3 end-3">
                            <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                    data-hs-overlay="#entity-profile-delete-modal" type="button">
                                <span class="sr-only">{{ __('Tutup') }}</span>
                                <i class="size-5" data-lucide="x"></i>
                            </button>
                        </div>
                        <div class="text-center">
                            <img alt="" class="size-12 mx-auto" src="/images/delete.png"/>
                            <div class="mt-5 text-center">
                                <h5 class="mb-1 text-lg font-semibold text-default-800">{{ __('Anda yakin?') }}</h5>
                                <p class="text-default-500 text-sm font-normal">
                                    {{ __('Profil entitas ":name" akan dihapus permanen.', ['name' => $entity->name]) }}
                                </p>
                                <div class="mt-5 flex gap-2 justify-center">
                                    <button class="btn text-danger bg-transparent hover:bg-danger/10"
                                            data-hs-overlay="#entity-profile-delete-modal" type="button">
                                        {{ __('Batal') }}
                                    </button>
                                    <form action="{{ route('master.entitas.profile.destroy', $entity) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="bg-danger text-white btn border-0 btn-sm" type="submit">
                                            {{ __('Ya, Hapus!') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    @endif
@endsection

@section('scripts')

@endsection
