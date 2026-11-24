<!-- Start Sidebar -->
<aside class="app-menu" id="app-menu">
    <!-- Sidenav Menu Brand Logo -->
    <a class="logo-box sticky top-0 flex min-h-topbar-height items-center justify-start px-6 backdrop-blur-xs border-b border-default-200"
        href="{{ route('dashboards.index') }}">
        <!-- Light Brand Logo -->
        <div class="logo-light">
            <img alt="Light logo" class="logo-lg h-6" src="{{ asset('images/logo-light.png') }}" />
            <img alt="Small logo" class="logo-sm h-6" src="{{ asset('images/logo-sm.png') }}" />
        </div>
        <!-- Dark Brand Logo -->
        <div class="logo-dark">
            <img alt="Dark logo" class="logo-lg h-6" src="{{ asset('images/logo-dark.png') }}" />
            <img alt="Small logo" class="logo-sm h-6" src="{{ asset('images/logo-sm.png') }}" />
        </div>
    </a>
    <!-- Sidenav Menu Toggle Button -->
    <div class="absolute top-0 end-5 flex h-topbar items-center justify">
        <button class="" id="button-hover-toggle">
            <i class="iconify tabler--circle size-5"></i>
        </button>
    </div>
    <!-- Sidenav Menu Item Link -->
    <div class="relative min-h-0 flex-grow">
        <div class="size-full" data-simplebar="">
            @include('layouts.partials/menu')
        </div>
    </div>
</aside>
