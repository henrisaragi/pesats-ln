@php
    $menuSections = [
        [
            'title' => __('Home'),
            'items' => [
                [
                    'label' => __('Dashboard'),
                    'tour' => __('Halaman beranda: ringkasan status verifikasi komoditas dan assesment pada Tahun Aktif.'),
                    'icon' => 'layout-dashboard',
                    'route' => 'dashboards.index',
                    'permission' => null,
                ],
            ],
        ],
        [
            'title' => __('Administrasi'),
            'items' => [
                [
                    'label' => __('Role'),
                    'tour' => __('Kelola role (peran) pengguna beserta hak akses yang dimiliki tiap role.'),
                    'icon' => 'users-round',
                    'route' => 'roles.index',
                    'permission' => 'roles.view',
                ],
                [
                    'label' => __('Permission'),
                    'tour' => __('Kelola daftar permission yang dapat diberikan ke role.'),
                    'icon' => 'key-round',
                    'route' => 'permissions.index',
                    'permission' => 'permissions.view',
                ],
                [
                    'label' => __('Entitas'),
                    'tour' => __('Kelola data entitas (perusahaan, penangkaran, dll.) beserta user yang terdaftar di dalamnya.'),
                    'icon' => 'building-2',
                    'children' => [
                        [
                            'label' => __('Entitas'),
                            'route' => 'master.entitas.index',
                            'permission' => 'entitas.view',
                        ],
                        [
                            'label' => __('User'),
                            'route' => 'users.index',
                            'permission' => 'users.view',
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => __('Layanan Entitas'),
            'items' => [
                [
                    'label' => __('Kuota'),
                    'tour' => __('Lihat kuota perusahaan Anda beserta realisasinya.'),
                    'icon' => 'gauge',
                    'route' => 'kuota-saya.index',
                    'permission' => 'kuota-saya.view',
                ],
                [
                    'label' => __('Kuota Ekspor Saya'),
                    'tour' => __('Lihat pembagian kuota ekspor yang dialokasikan untuk entitas Anda.'),
                    'icon' => 'fish',
                    'route' => 'kuota-tangkap-saya.index',
                    'permission' => 'kuota.view-own',
                ],
                [
                    'label' => __('Customer'),
                    'tour' => __('Kelola data customer (pembeli) tujuan ekspor Anda.'),
                    'icon' => 'contact',
                    'route' => 'customer.index',
                    'permission' => 'customer.view',
                ],
                [
                    'label' => __('Komoditas Entitas'),
                    'tour' => __('Ajukan dan pantau sertifikat komoditas yang dimiliki entitas Anda.'),
                    'icon' => 'package',
                    'route' => 'komoditas-entitas.index',
                    'permission' => 'komoditas-entitas.view',
                ],
                [
                    'label' => __('Verifikasi Komoditas'),
                    'tour' => __('Verifikasi pengajuan sertifikat komoditas dari entitas.'),
                    'icon' => 'shield-check',
                    'route' => 'komoditas-entitas.verifikasi.index',
                    'permission' => 'komoditas-entitas.verify',
                ],
                [
                    'label' => __('Assesment'),
                    'tour' => __('Isi assesment untuk komoditas yang sudah terverifikasi dan pantau hasilnya.'),
                    'icon' => 'clipboard-check',
                    'route' => 'assesment-isi.index',
                    'permission' => 'assesment-isi.view',
                ],
                [
                    'label' => __('Verifikasi Assesment'),
                    'tour' => __('Periksa dan verifikasi assesment yang sudah dikirim entitas.'),
                    'icon' => 'shield-check',
                    'route' => 'assesment-isi.verifikasi.index',
                    'permission' => 'assesment-isi.verify',
                ],
                [
                    'label' => __('SATS-LN Ekspor'),
                    'tour' => __('Ajukan dan pantau permohonan SATS-LN ekspor.'),
                    'icon' => 'file-text',
                    'route' => 'permohonan-export.index',
                    'permission' => 'permohonan-export.view',
                ],
                [
                    'label' => __('Verifikasi Ekspor'),
                    'tour' => __('Verifikasi permohonan SATS-LN ekspor, termasuk daftar verifikasi yang ditugaskan kepada Anda.'),
                    'icon' => 'shield-check',
                    'children' => [
                        [
                            'label' => __('Verifikasi Ekspor'),
                            'route' => 'permohonan-export.verifikasi.index',
                            'permission' => 'permohonan.verify.view',
                        ],
                        [
                            'label' => __('Verifikasi Saya'),
                            'route' => 'permohonan-export.verifikasi.mine',
                            'permission' => 'permohonan-export.verify',
                        ],
                    ],
                ],
                [
                    'label' => __('Chat'),
                    'tour' => __('Percakapan langsung antara entitas dan petugas layanan.'),
                    'icon' => 'messages-square',
                    'route' => 'chat.index',
                    'permission' => ['chat.start', 'chat.respond'],
                ],
            ],
        ],
        [
            'title' => __('Master Data'),
            'items' => [
                [
                    'label' => __('Master Data'),
                    'tour' => __('Kelola data referensi: wilayah, pelabuhan, komoditas, spesies, assesment, dan lainnya.'),
                    'icon' => 'database',
                    'children' => [
                        [
                            'label' => __('Negara'),
                            'route' => 'master.countries.index',
                            'permission' => 'countries.view',
                        ],
                        [
                            'label' => __('Provinsi'),
                            'route' => 'master.provinsi.index',
                            'permission' => 'provinsi.view',
                        ],
                        [
                            'label' => __('Kota'),
                            'route' => 'master.kota.index',
                            'permission' => 'kota.view',
                        ],
                        [
                            'label' => __('Kecamatan'),
                            'route' => 'master.kecamatan.index',
                            'permission' => 'kecamatan.view',
                        ],
                        [
                            'label' => __('Kelurahan'),
                            'route' => 'master.kelurahan.index',
                            'permission' => 'kelurahan.view',
                        ],
                        [
                            'label' => __('Pelabuhan'),
                            'route' => 'master.ports.index',
                            'permission' => 'ports.view',
                        ],
                        [
                            'label' => __('Kode Perdagangan'),
                            'route' => 'master.trade-codes.index',
                            'permission' => 'trade-codes.view',
                        ],
                        [
                            'label' => __('Komoditas'),
                            'route' => 'master.komoditas.index',
                            'permission' => 'komoditas.view',
                        ],
                        [
                            'label' => __('Status Asal'),
                            'route' => 'master.source-statuses.index',
                            'permission' => 'source-statuses.view',
                        ],
                        [
                            'label' => __('Spesies'),
                            'route' => 'master.species.index',
                            'permission' => 'species.view',
                        ],
                        [
                            'label' => __('Tujuan Transaksi'),
                            'route' => 'master.purpose-of-transactions.index',
                            'permission' => 'purpose-of-transactions.view',
                        ],
                        [
                            'label' => __('Assesment'),
                            'route' => 'assesment.index',
                            'permission' => 'assesment.view',
                        ],
                    ],
                ],
                [
                    'label' => __('Database Kuota'),
                    'tour' => __('Kelola kuota nasional, kuota perusahaan, serta pembagian dan pengaturan kuota ekspor.'),
                    'icon' => 'gauge',
                    'children' => [
                        [
                            'label' => __('Kuota Nasional'),
                            'route' => 'master.kuota-nasional.index',
                            'permission' => 'kuota-nasional.view',
                        ],
                        [
                            'label' => __('Kuota Perusahaan'),
                            'route' => 'kuota-perusahaan.index',
                            'permission' => 'kuota-perusahaan.view',
                        ],
                        [
                            'label' => __('Pembagian Kuota Ekspor'),
                            'route' => 'kuota-tangkap.index',
                            'permission' => 'kuota.view',
                        ],
                        [
                            'label' => __('Pengaturan Kuota Ekspor'),
                            'route' => 'kuota-tangkap.settings.edit',
                            'permission' => 'kuota.settings',
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => __('Dokumen'),
            'items' => [
                [
                    'label' => __('Dokumen'),
                    'tour' => __('Kumpulan dokumen yang dapat diunduh.'),
                    'icon' => 'file-text',
                    'route' => 'documents.index',
                    'permission' => 'documents.view',
                ],
                [
                    'label' => __('Panduan'),
                    'tour' => __('Template surat pernyataan dan video panduan penggunaan aplikasi.'),
                    'icon' => 'book-open',
                    'children' => [
                        [
                            'label' => __('Template Surat Pernyataan'),
                            'route' => 'template-surat-pernyataan.index',
                            'permission' => 'template-surat-pernyataan.view',
                        ],
                        [
                            'label' => __('Video Panduan'),
                            'route' => 'video-panduan.index',
                            'permission' => 'video-panduan.view',
                        ],
                    ],
                ],
            ],
        ],
        [
            'title' => __('Settings'),
            'items' => [
                [
                    'label' => __('Whatsapp'),
                    'tour' => __('Pengaturan integrasi notifikasi WhatsApp.'),
                    'icon' => 'message-circle',
                    'route' => 'settings.whatsapp.index',
                    'permission' => 'settings.whatsapp.manage',
                ],
                [
                    'label' => __('Status Server'),
                    'tour' => __('Pantau status proses dan layanan server.'),
                    'icon' => 'server',
                    'route' => 'settings.server-status.index',
                    'permission' => 'settings.server-status.view',
                ],
            ],
        ],
    ];

    $userCanSeeMenu = function (string|array|null $permission) {
        if ($permission === null) {
            return true;
        }

        if (!auth()->check()) {
            return false;
        }

        return is_array($permission) ? auth()->user()->canAny($permission) : auth()->user()->can($permission);
    };

    $isMenuItemVisible = function (array $item) use (&$isMenuItemVisible, $userCanSeeMenu) {
        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if ($isMenuItemVisible($child)) {
                    return true;
                }
            }

            return false;
        }

        return $userCanSeeMenu($item['permission'] ?? null);
    };
@endphp

<ul class="side-nav p-3 hs-accordion-group">
    @foreach ($menuSections as $section)
        @php $visibleItems = collect($section['items'])->filter($isMenuItemVisible); @endphp
        @if ($visibleItems->isNotEmpty())
            <li class="menu-title">
                <span>{{ $section['title'] }}</span>
            </li>
            @foreach ($visibleItems as $item)
                @if (!empty($item['children']))
                    @php $visibleChildren = collect($item['children'])->filter($isMenuItemVisible); @endphp
                    <li class="menu-item hs-accordion" data-sidebar-tour
                        data-tour-title="{{ $item['label'] }}" data-tour-description="{{ $item['tour'] }}">
                        <a class="hs-accordion-toggle menu-link" href="javascript:void(0)">
                            <span class="menu-icon"><i data-lucide="{{ $item['icon'] }}"></i></span>
                            <span class="menu-text">{{ $item['label'] }}</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul class="sub-menu hs-accordion-content hidden">
                            @foreach ($visibleChildren as $child)
                                <li class="menu-item">
                                    <a class="menu-link" href="{{ route($child['route']) }}">
                                        <span class="menu-text">{{ $child['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    <li class="menu-item" data-sidebar-tour
                        data-tour-title="{{ $item['label'] }}" data-tour-description="{{ $item['tour'] }}">
                        <a class="menu-link" href="{{ route($item['route']) }}">
                            <span class="menu-icon"><i data-lucide="{{ $item['icon'] }}"></i></span>
                            <div class="menu-text">{{ $item['label'] }}</div>
                        </a>
                    </li>
                @endif
            @endforeach
        @endif
    @endforeach
</ul>
