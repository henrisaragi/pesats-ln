{{--
    Notification bell dropdown (Preline hs-dropdown, same mechanism as the other topbar
    dropdowns - language/theme/profile). Content is populated client-side by
    resources/js/components/notification-dropdown.js from GET /notifications - this file only
    renders the shell + the "Permohonan" tab's static empty state (no data source yet).
--}}
<div class="topbar-item hs-dropdown [--auto-close:inside] relative inline-flex" id="{{ $id }}">
    <button aria-expanded="false" aria-haspopup="menu" aria-label="{{ __('Notifikasi') }}"
            class="hs-dropdown-toggle btn btn-icon size-8 hover:bg-default-150 rounded-full relative" type="button">
        <i class="size-4.5" data-lucide="bell-ring"></i>
        <span class="hidden absolute -end-1 -top-1 min-w-4.5 h-4.5 px-1 text-[10px] font-semibold bg-danger rounded-full text-white flex items-center justify-center"
              data-notification-badge>0</span>
    </button>
    <div class="hs-dropdown-menu max-w-100 p-0" role="menu">
        <!-- Header -->
        <div class="p-4 border-b border-default-200 flex items-center justify-between gap-2">
            <h3 class="text-base text-default-800">{{ __('Notifikasi') }}</h3>
            <button class="text-xs font-medium text-primary hover:underline" data-notification-mark-all type="button">
                {{ __('Tandai Semua Dibaca') }}
            </button>
        </div>
        <!-- Tabs -->
        <nav aria-label="Tabs" aria-orientation="horizontal"
             class="flex gap-x-1 bg-default-150 p-2 border-b border-default-200 overflow-x-auto" role="tablist">
            <button aria-controls="{{ $id }}-tab-all" aria-selected="true"
                    class="hs-tab-active:bg-card hs-tab-active:text-primary py-1 px-3 rounded font-semibold inline-flex items-center gap-x-2 text-xs whitespace-nowrap text-default-500 hover:text-primary active"
                    data-hs-tab="#{{ $id }}-tab-all" data-notification-tab="all" role="tab" type="button">
                {{ __('Lihat Semua') }}
            </button>
            @foreach ($categories as $category)
                <button aria-controls="{{ $id }}-tab-{{ $category->value }}" aria-selected="false"
                        class="hs-tab-active:bg-card hs-tab-active:text-primary py-1 px-3 rounded font-semibold inline-flex items-center gap-x-2 text-xs whitespace-nowrap text-default-500 hover:text-primary"
                        data-hs-tab="#{{ $id }}-tab-{{ $category->value }}"
                        data-notification-tab="{{ $category->value }}" role="tab" type="button">
                    {{ $category->label() }}
                </button>
            @endforeach
        </nav>
        <!-- Tabs content -->
        <div class="h-80" data-simplebar>
            <div aria-labelledby="{{ $id }}-tab-all-item" id="{{ $id }}-tab-all" role="tabpanel">
                <div data-notification-list="all"></div>
                <p class="hidden p-6 text-center text-sm text-default-500" data-notification-empty="all">
                    {{ __('Belum ada notifikasi.') }}
                </p>
            </div>
            @foreach ($categories as $category)
                <div aria-labelledby="{{ $id }}-tab-{{ $category->value }}-item" class="hidden"
                     id="{{ $id }}-tab-{{ $category->value }}" role="tabpanel">
                    @if ($category === \App\Enums\NotificationCategory::Permohonan)
                        <p class="p-6 text-center text-sm text-default-500">{{ __('Belum ada permohonan.') }}</p>
                    @else
                        <div data-notification-list="{{ $category->value }}"></div>
                        <p class="hidden p-6 text-center text-sm text-default-500"
                           data-notification-empty="{{ $category->value }}">
                            {{ __('Belum ada notifikasi.') }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
