@extends('layouts.vertical', ['title' => 'Chat'])

@section('css')

@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Layanan Entitas', 'title' => 'Chat'])

    {{--
        Styling (sidebar list item, message bubbles, compose bar) mirrors
        resources/views/apps/chat.blade.php (Tailwick demo) - the icon rail, mic/image/call/
        video buttons, and the never-wired "panel-right-open" info drawer from that template
        were dropped since they're decorative-only there too and weren't asked for here.
        Everything else (list content, message thread, sending, claiming, real-time updates) is
        driven by resources/js/pages/chat.js against routes/chat.php.
    --}}
    <div class="card overflow-hidden" data-chat-page data-chat-can-respond="{{ $canRespond ? '1' : '0' }}"
         data-chat-can-start="{{ $canStart ? '1' : '0' }}">
        <div class="flex">
            <div class="w-80 border-e border-default-200 shrink-0 flex flex-col">
                <div class="card-body border-b border-default-200 shrink-0">
                    <div class="flex justify-between items-center">
                        <h6 class="card-title">{{ __('Percakapan') }}</h6>
                        @if ($canStart)
                            <button aria-label="{{ __('Percakapan Baru') }}" class="size-8 btn bg-primary text-white"
                                    data-hs-overlay="#chat-new-modal" type="button">
                                <i class="size-4" data-lucide="plus"></i>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="grow min-h-0 overflow-y-auto lg:h-[calc(100vh-310px)] h-[calc(100vh-125px)]" data-simplebar>
                    @if ($canRespond)
                        <div class="px-4 pt-3 pb-1">
                            <p class="text-xs font-semibold text-default-500 uppercase">{{ __('Antrian') }}</p>
                        </div>
                        <div data-chat-list="queue"></div>
                        <p class="px-4 py-3 text-xs text-default-500" data-chat-empty="queue">
                            {{ __('Tidak ada antrian.') }}
                        </p>
                    @endif
                    <div class="px-4 pt-3 pb-1">
                        <p class="text-xs font-semibold text-default-500 uppercase">{{ __('Percakapan Saya') }}</p>
                    </div>
                    <div data-chat-list="mine"></div>
                    <p class="px-4 py-3 text-xs text-default-500" data-chat-empty="mine">
                        {{ __('Belum ada percakapan.') }}
                    </p>
                    <div class="px-4 pt-3 pb-1">
                        <p class="text-xs font-semibold text-default-500 uppercase">{{ __('Riwayat') }}</p>
                    </div>
                    <div data-chat-list="history"></div>
                    <p class="px-4 py-3 text-xs text-default-500" data-chat-empty="history">
                        {{ __('Belum ada riwayat.') }}
                    </p>
                </div>
            </div>
            <div class="w-full flex flex-col min-h-0 lg:h-[calc(100vh-230px)] h-[calc(100vh-125px)]">
                <div class="flex flex-col items-center justify-center h-full text-center text-default-500 px-10"
                     data-chat-placeholder>
                    <i class="size-10 mb-3" data-lucide="messages-square"></i>
                    <p class="text-sm">{{ __('Pilih percakapan di sebelah kiri untuk mulai.') }}</p>
                </div>
                <div class="hidden flex-col h-full min-h-0" data-chat-panel>
                    <div class="card-body border-b border-default-200 shrink-0">
                        <div class="gap-3 flex justify-between items-center">
                            <div>
                                <h6 class="text-default-800 mb-1 font-bold text-sm" data-chat-panel-name></h6>
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.75 border text-xs"
                                      data-chat-panel-status></span>
                            </div>
                            <button class="hidden btn btn-sm bg-danger text-white" data-chat-end type="button">
                                {{ __('Akhiri Percakapan') }}
                            </button>
                        </div>
                    </div>
                    <div class="grow min-h-0 overflow-y-auto" data-simplebar>
                        <div class="card-body bg-default-50">
                            <div class="flex flex-col gap-5" data-chat-messages></div>
                        </div>
                    </div>
                    <div class="card-body border-t border-default-200 shrink-0">
                        <form class="flex gap-2 items-center" data-chat-form>
                            <input class="form-input w-full" name="body"
                                   placeholder="{{ __('Ketik pesan...') }}" type="text"/>
                            <button class="btn bg-primary text-white" type="submit">
                                <i class="size-4 me-1" data-lucide="send"></i>{{ __('Kirim') }}
                            </button>
                        </form>
                        <p class="hidden text-xs text-default-500 mt-2" data-chat-closed-note>
                            {{ __('Percakapan ini sudah selesai.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canStart)
        <div aria-labelledby="chat-new-modal-label" id="chat-new-modal"
             class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
             role="dialog" tabindex="-1">
            <div
                class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
                <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
                    <div class="card-header">
                        <h3 class="font-bold text-default-800 text-base" id="chat-new-modal-label">
                            {{ __('Percakapan Baru') }}
                        </h3>
                        <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                                data-hs-overlay="#chat-new-modal" type="button">
                            <span class="sr-only">{{ __('Tutup') }}</span>
                            <i class="size-5" data-lucide="x"></i>
                        </button>
                    </div>
                    <form data-chat-new-form>
                        <div class="p-4">
                            <label class="inline-block mb-2 text-sm font-medium" for="chat-new-body">
                                {{ __('Pesan') }}
                            </label>
                            <textarea class="form-input" id="chat-new-body" name="body" rows="4"
                                      placeholder="{{ __('Tulis pesan untuk memulai percakapan...') }}"></textarea>
                            <p class="hidden mt-1 text-xs text-danger" data-chat-new-error></p>
                        </div>
                        <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                            <button class="btn bg-default-150" data-hs-overlay="#chat-new-modal" type="button">
                                {{ __('Batal') }}
                            </button>
                            <button class="btn bg-primary text-white" type="submit">{{ __('Mulai') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        window.chatText = {
            waitingForStaff: "{{ __('Menunggu staf') }}",
            unreadCount: "{{ __('Pesan belum dibaca:') }}",
            startFailed: "{{ __('Gagal memulai percakapan.') }}",
            read: "{{ __('Dibaca') }}",
            sent: "{{ __('Terkirim') }}",
        };
    </script>
    @vite(['resources/js/pages/chat.js'])
@endsection
