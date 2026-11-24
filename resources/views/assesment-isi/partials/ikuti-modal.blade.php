{{--
    Modal sebelum mengikuti assesment (statis: hanya tertutup lewat ikon X / tombol Batal).
    Video & template dibuka di x-document-viewer-modal "assesment-panduan-viewer-modal" (fullscreen,
    tidak menutup modal ini). "Mulai Assesment" aktif setelah Surat Pernyataan dipilih - lihat
    resources/js/pages/assesment-isi-index.js; server tetap mewajibkannya (AssesmentIsiStoreRequest).
--}}
@php($isRedisplay = old('_modal') === 'assesment-ikuti-modal')
<div aria-labelledby="assesment-ikuti-modal-label" data-hs-overlay-keyboard="false" id="assesment-ikuti-modal"
     class="hs-overlay [--overlay-backdrop:static] hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
     role="dialog" tabindex="-1">
    <div
        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-xl sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center whitespace-normal">
        <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
            <div class="card-header">
                <div>
                    <h3 class="font-bold text-default-800 text-base" id="assesment-ikuti-modal-label">{{ __('Ikuti Assesment') }}</h3>
                    <p class="text-xs text-default-500">{{ __('Pelajari panduan dan lengkapi Surat Pernyataan sebelum memulai assesment.') }}</p>
                </div>
                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800" data-hs-overlay="#assesment-ikuti-modal" type="button">
                    <span class="sr-only">{{ __('Tutup') }}</span>
                    <i class="size-5" data-lucide="x"></i>
                </button>
            </div>
            <form action="{{ route('assesment-isi.store') }}" data-assesment-ikuti-form enctype="multipart/form-data" method="POST">
                @csrf
                <input name="_modal" type="hidden" value="assesment-ikuti-modal">
                <input data-assesment-ikuti-assesment-id name="assesment_id" type="hidden" value="{{ $isRedisplay ? old('assesment_id') : '' }}">
                <input data-assesment-ikuti-komoditas-id name="komoditas_id" type="hidden" value="{{ $isRedisplay ? old('komoditas_id') : '' }}">
                <div class="p-4 flex flex-col gap-5">
                    <section>
                        <h4 class="mb-1 text-sm font-semibold text-default-800 flex items-center gap-2">
                            <span class="inline-flex items-center justify-center size-5 rounded-full bg-primary text-white text-xs">1</span>
                            {{ __('Panduan Mengisi Assesment') }}
                        </h4>
                        <p class="mb-3 text-xs text-default-500">{{ __('Tonton video panduan berikut sebelum mengisi assesment.') }}</p>
                        @forelse ($videoPanduan as $video)
                            @include('panduan.partials.video-play-button', [
                                'video' => $video,
                                'class' => 'w-full flex items-center gap-2 text-start text-sm px-3 py-2 mb-2 rounded-md border border-default-200 hover:border-primary hover:text-primary',
                                'label' => $video->judul,
                                'viewerId' => 'assesment-panduan-viewer-modal',
                            ])
                        @empty
                            <p class="text-sm text-default-400">{{ __('Belum ada video panduan.') }}</p>
                        @endforelse
                    </section>
                    <section>
                        <h4 class="mb-1 text-sm font-semibold text-default-800 flex items-center gap-2">
                            <span class="inline-flex items-center justify-center size-5 rounded-full bg-primary text-white text-xs">2</span>
                            {{ __('Template Surat Pernyataan') }}
                        </h4>
                        <p class="mb-3 text-xs text-default-500">{{ __('Unduh template, isi, dan tanda tangani Surat Pernyataan.') }}</p>
                        @forelse ($templateSuratPernyataan as $template)
                            @include('panduan.partials.template-view-button', [
                                'template' => $template,
                                'class' => 'w-full flex items-center gap-2 text-start text-sm px-3 py-2 mb-2 rounded-md border border-default-200 hover:border-primary hover:text-primary',
                                'label' => $template->judul,
                                'viewerId' => 'assesment-panduan-viewer-modal',
                            ])
                        @empty
                            <p class="text-sm text-default-400">{{ __('Belum ada template surat pernyataan.') }}</p>
                        @endforelse
                    </section>
                    <section>
                        <h4 class="mb-1 text-sm font-semibold text-default-800 flex items-center gap-2">
                            <span class="inline-flex items-center justify-center size-5 rounded-full bg-primary text-white text-xs">3</span>
                            <label for="assesment-ikuti-surat-pernyataan">{{ __('Upload Surat Pernyataan') }}</label>
                        </h4>
                        <p class="mb-3 text-xs text-default-500">{{ __('Unggah Surat Pernyataan yang sudah ditandatangani (PDF/JPG/PNG, maks. 5 MB).') }}</p>
                        <input accept=".pdf,.jpg,.jpeg,.png" class="form-input" data-assesment-ikuti-file id="assesment-ikuti-surat-pernyataan"
                               name="surat_pernyataan" type="file"/>
                        @if ($isRedisplay && $errors->any())
                            <p class="mt-1 text-xs text-danger">{{ $errors->first('surat_pernyataan') ?: $errors->first() }}</p>
                        @endif
                    </section>
                </div>
                <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                    <button class="btn bg-default-150" data-hs-overlay="#assesment-ikuti-modal" type="button">{{ __('Batal') }}</button>
                    <button class="btn bg-primary text-white disabled:opacity-50 disabled:cursor-not-allowed" data-assesment-ikuti-submit disabled type="submit">
                        {{ __('Mulai Assesment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
