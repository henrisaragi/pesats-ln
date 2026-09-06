@php
    $topbarYears = \App\Models\KuotaNasional::query()->select('year')->distinct()->orderByDesc('year')->pluck('year');

    if ($topbarYears->isEmpty()) {
        $topbarYears = collect([now()->year]);
    }
@endphp

<!-- Topbar Start -->
<div
    class="app-header min-h-topbar-height flex items-center sticky top-0 z-30 bg-(--topbar-background) border-b border-default-200">
    <div class="w-full flex items-center justify-between px-6">
        <div class="flex items-center gap-5">
            <!-- Sidenav Menu Toggle Button -->
            <button class="btn btn-icon size-8 hover:bg-default-150 rounded" id="button-toggle-menu">
                <i class="iconify lucide--align-left text-xl"></i>
            </button>
            <!-- Topbar Search -->
            <div class="lg:flex hidden items-center relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <i class="iconify tabler--search text-base"></i>
                </div>
                <input class="form-input px-12 text-sm rounded border-transparent focus:border-transparent w-60"
                    id="topbar-search" placeholder="Search something..." type="search" />
                <button class="absolute inset-y-0 end-0 flex items-center pe-4" type="button">
                    <span class="ms-auto font-medium">⌘ K</span>
                </button>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- Language Dropdown Button -->
            <div class="topbar-item hs-dropdown [--placement:bottom-right] relative inline-flex">
                <button aria-expanded="false" aria-haspopup="menu" aria-label="Dropdown"
                    class="hs-dropdown-toggle btn btn-icon size-8 hover:bg-default-150 rounded-full relative"
                    type="button">
                    <img alt="" class="size-4.5 rounded"
                        src="{{ app()->getLocale() === 'id' ? '/images/flags/indonesia.svg' : '/images/flags/us.jpg' }}" />
                </button>
                <div aria-labelledby="dropdown-menu" aria-orientation="vertical" class="hs-dropdown-menu"
                    role="menu">
                    <a class="flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded {{ app()->getLocale() === 'id' ? 'bg-default-150' : '' }}"
                        href="{{ route('locale.switch', 'id') }}">
                        <img alt="" class="size-4 rounded-full" src="/images/flags/indonesia.svg" />
                        Indonesia
                    </a>
                    <a class="flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded {{ app()->getLocale() === 'en' ? 'bg-default-150' : '' }}"
                        href="{{ route('locale.switch', 'en') }}">
                        <img alt="" class="size-4 rounded-full" src="/images/flags/us.jpg" />
                        English
                    </a>
                </div>
            </div>
            <!-- Light/Dark Mode Button -->
            <div class="topbar-item">
                <button class="btn btn-icon size-8 hover:bg-default-150 transition-[scale,background] rounded-full"
                    id="light-dark-mode" type="button">
                    <i
                        class="iconify tabler--moon text-xl absolute dark:scale-0 dark:-rotate-90 scale-100 rotate-0 transition-all duration-200"></i>
                    <i
                        class="iconify tabler--sun text-xl absolute dark:scale-100 dark:rotate-0 scale-0 rotate-90 transition-all duration-200"></i>
                </button>
            </div>
            <!-- Notification Button -->
            <x-notification-dropdown />
            <!-- Year Dropdown Button -->
            <div class="topbar-item hs-dropdown [--placement:bottom-right] relative inline-flex">
                <button aria-expanded="false" aria-haspopup="menu" aria-label="{{ __('Tahun Aktif') }}"
                    class="hs-dropdown-toggle btn btn-icon size-8 hover:bg-default-150 rounded-full relative"
                    id="topbar-year-toggle" type="button">
                    <i class="size-4.5" data-lucide="calendar"></i>
                </button>
                <div aria-labelledby="topbar-year-toggle" aria-orientation="vertical" class="hs-dropdown-menu"
                    role="menu">
                    @foreach ($topbarYears as $year)
                        <a class="topbar-year-option flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded"
                            data-year="{{ $year }}" href="#">
                            {{ $year }}
                        </a>
                    @endforeach
                </div>
            </div>
            <!-- Fullscreen Button -->
            <div class="topbar-item">
                <button aria-label="Full Screen" class="btn btn-icon size-8 hover:bg-default-150 rounded-full group"
                    data-toggle="fullscreen" id="fullscreenBtn" type="button">
                    <i class="iconify lucide--fullscreen size-4.5 group-[.fullscreen-active]:hidden"></i>
                    <i class="iconify lucide--minimize size-4.5 hidden group-[.fullscreen-active]:inline-block"></i>
                </button>
            </div>
            <!-- Profile Dropdown Button -->
            <div class="topbar-item hs-dropdown relative inline-flex">
                <button aria-expanded="false" aria-haspopup="menu" aria-label="Dropdown"
                    class="cursor-pointer bg-pink-100 rounded-full">
                    <span
                        class="hs-dropdown-toggle rounded-full size-9.5 flex items-center justify-center font-semibold text-primary bg-primary/10">{{ Auth::user()->initials() }}</span>
                </button>
                <div aria-labelledby="hs-dropdown-with-icons" aria-orientation="vertical"
                    class="hs-dropdown-menu min-w-48" role="menu">
                    <div class="p-2">
                        <h6 class="mb-2 text-default-500">Welcome to <i>PeSATS</i></h6>
                        <a class="flex gap-3" href="#!">
                            <div class="relative inline-block">
                                <div
                                    class="size-12 rounded flex items-center justify-center font-semibold text-primary bg-primary/10">
                                    {{ Auth::user()->initials() }}
                                </div>
                                <span
                                    class="-top-1 -end-1 absolute w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full"></span>
                            </div>
                            <div>
                                <h6 class="mb-1 text-sm font-semibold text-default-800">
                                    {{ Auth::user()->entity->name ?? '' }}
                                </h6>
                                <p class="text-default-500">{{ Auth::user()->email }}</p>
                            </div>
                        </a>
                    </div>
                    <div class="border-t border-t-default-200 -mx-2 my-2"></div>
                    <div class="flex flex-col gap-y-1">
                        <a class="flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded"
                            href="{{ route('second', ['apps', 'mailbox']) }}">
                            <i class="size-4" data-lucide="mail"></i>
                            Inbox
                            <span
                                class="size-4.5 font-semibold bg-danger rounded text-white flex items-center justify-center text-xs">15</span>
                        </a>
                        <a class="flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded"
                            href="{{ route('chat.index') }}">
                            <i class="size-4" data-lucide="messages-square"></i>
                            Chat
                        </a>
                        <a class="flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded"
                            href="{{ route('second', ['pages', 'pricing']) }}">
                            <i class="size-4" data-lucide="gem"></i>
                            Upgrade Pro
                        </a>
                        <div class="border-t border-default-200 -mx-2 my-1"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button
                                class="flex items-center gap-x-3.5 py-1.5 font-medium px-3 text-default-600 hover:bg-default-150 rounded w-full text-left"
                                type="submit">
                                <i class="size-4" data-lucide="log-out"></i>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Topbar End -->

<x-notification-alert-modal />

<script>
    (function() {
        var STORAGE_KEY = 'tahun_aktif';
        var options = document.querySelectorAll('.topbar-year-option');

        function highlightActiveYear(activeYear) {
            options.forEach(function(option) {
                option.classList.toggle('bg-default-150', option.dataset.year === activeYear);
            });
        }

        var activeYear = localStorage.getItem(STORAGE_KEY) || String(new Date().getFullYear());
        highlightActiveYear(activeYear);
        console.log('Tahun aktif:', activeYear);

        options.forEach(function(option) {
            option.addEventListener('click', function(event) {
                event.preventDefault();

                var year = this.dataset.year;
                localStorage.setItem(STORAGE_KEY, year);
                highlightActiveYear(year);
                console.log('Tahun aktif:', year);
                window.dispatchEvent(new CustomEvent('active-year-changed', {
                    detail: {
                        year: year
                    }
                }));
            });
        });
    })();
</script>
