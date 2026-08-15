@props([
    'step' => null,
    'title',
    'subtitle' => null,
])

@php
    $onboardingTourText = [
        'steps' => [
            'title' => __('Progres Pengisian'),
            'description' => __('Ikuti langkah-langkah berikut untuk melengkapi data entitas Anda. Setiap kali Anda klik "Selanjutnya", data langsung tersimpan.'),
        ],
        'type' => [
            'options' => [
                'title' => __('Pilih Jenis Entitas'),
                'description' => __('Klik salah satu kartu sesuai jenis entitas Anda: Perusahaan, Penangkaran, Perorangan, atau UPT KSDA.'),
            ],
            'next' => [
                'title' => __('Lanjutkan'),
                'description' => __('Setelah memilih, klik tombol ini untuk menyimpan pilihan Anda dan melanjutkan ke langkah berikutnya.'),
            ],
        ],
        'entitas' => [
            'name' => [
                'title' => __('Nama Entitas'),
                'description' => __('Isi nama resmi entitas Anda (nama perusahaan, penangkaran, atau nama pribadi).'),
            ],
            'address' => [
                'title' => __('Alamat'),
                'description' => __('Isi alamat lengkap entitas Anda.'),
            ],
            'region' => [
                'title' => __('Wilayah'),
                'description' => __('Pilih Provinsi, Kota, Kecamatan, lalu Kelurahan secara berurutan - setiap pilihan akan memuat pilihan berikutnya secara otomatis.'),
            ],
            'next' => [
                'title' => __('Lanjutkan'),
                'description' => __('Klik tombol ini untuk menyimpan data entitas dan melanjutkan.'),
            ],
        ],
        'detail' => [
            'fields' => [
                'title' => __('Data Pendukung'),
                'description' => __('Lengkapi field tambahan sesuai jenis entitas Anda (misalnya NPWP/NIB, Nomor Izin Penangkaran, NIK, atau Kode UPT).'),
            ],
            'next' => [
                'title' => __('Selesaikan'),
                'description' => __('Klik tombol ini untuk menyimpan data dan menyelesaikan proses pengisian.'),
            ],
        ],
        'complete' => [
            'title' => __('Selesai!'),
            'description' => __('Data entitas Anda sudah lengkap. Klik "Ke Dashboard" untuk mulai menggunakan aplikasi.'),
        ],
        'buttons' => [
            'next' => __('Selanjutnya'),
            'prev' => __('Sebelumnya'),
            'done' => __('Selesai'),
        ],
    ];
@endphp

<div
    class="relative flex items-center justify-center w-full min-h-screen overflow-hidden bg-gradient-to-r from-blue-900 to-blue-800 dark:to-blue-900 dark:from-blue-950 px-4 py-10">
    <div class="absolute inset-0 opacity-20">
        <img alt="" src="/images/modern.svg"/>
    </div>
    <div class="w-full max-w-2xl mx-auto py-10 px-8 bg-card rounded-md relative z-10">
        <button aria-label="{{ __('Tur Panduan') }}"
                class="absolute top-4 end-4 btn btn-sm bg-default-150 text-default-700 hover:bg-default-200"
                id="onboarding-tour-trigger" type="button">
            <i class="size-4 me-1" data-lucide="help-circle"></i>{{ __('Tur Panduan') }}
        </button>
        <div class="text-center mb-6">
            <img alt="" class="h-7 mx-auto mb-6 dark:hidden" src="/images/logo-dark.png"/>
            <img alt="" class="h-7 mx-auto mb-6 hidden dark:block" src="/images/logo-light.png"/>
            <h3 class="text-xl font-semibold text-default-900">{{ $title }}</h3>
            @if ($subtitle)
                <p class="text-default-500 text-sm mt-1">{{ $subtitle }}</p>
            @endif
        </div>

        @if ($step)
            @include('onboarding.partials.steps', ['currentStep' => $step])
        @endif

        {{ $slot }}
    </div>
</div>

<script>
    window.onboardingTourText = @json($onboardingTourText);
</script>
