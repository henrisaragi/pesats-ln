@php
    $menuSections = [
        [
            'title' => __('Administrasi'),
            'items' => [
                [
                    'label' => __('Role'),
                    'icon' => 'users-round',
                    'route' => 'roles.index',
                    'permission' => 'roles.view',
                ],
                [
                    'label' => __('Permission'),
                    'icon' => 'key-round',
                    'route' => 'permissions.index',
                    'permission' => 'permissions.view',
                ],
                [
                    'label' => __('Entitas'),
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
                    'icon' => 'gauge',
                    'route' => 'kuota-saya.index',
                    'permission' => 'kuota-saya.view',
                ],
                [
                    'label' => __('Customer'),
                    'icon' => 'contact',
                    'route' => 'customer.index',
                    'permission' => 'customer.view',
                ],
                [
                    'label' => __('Komoditas Entitas'),
                    'icon' => 'package',
                    'route' => 'komoditas-entitas.index',
                    'permission' => 'komoditas-entitas.view',
                ],
                [
                    'label' => __('Verifikasi Komoditas'),
                    'icon' => 'shield-check',
                    'route' => 'komoditas-entitas.verifikasi.index',
                    'permission' => 'komoditas-entitas.verify',
                ],
                [
                    'label' => __('Assesment'),
                    'icon' => 'clipboard-check',
                    'route' => 'assesment-isi.index',
                    'permission' => 'assesment-isi.view',
                ],
                [
                    'label' => __('Verifikasi Assesment'),
                    'icon' => 'shield-check',
                    'route' => 'assesment-isi.verifikasi.index',
                    'permission' => 'assesment-isi.verify',
                ],
                [
                    'label' => __('SATS-LN Ekspor'),
                    'icon' => 'file-text',
                    'route' => 'permohonan-export.index',
                    'permission' => 'permohonan-export.view',
                ],
                [
                    'label' => __('Verifikasi Ekspor'),
                    'icon' => 'shield-check',
                    'route' => 'permohonan-export.verifikasi.index',
                    'permission' => 'permohonan.verify.view',
                ],
                [
                    'label' => __('Chat'),
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
                    ],
                ],
            ],
        ],
        [
            'title' => __('Dokumen'),
            'items' => [
                [
                    'label' => __('Dokumen'),
                    'icon' => 'file-text',
                    'route' => 'documents.index',
                    'permission' => 'documents.view',
                ],
            ],
        ],
        [
            'title' => __('Settings'),
            'items' => [
                [
                    'label' => __('Whatsapp'),
                    'icon' => 'message-circle',
                    'route' => 'settings.whatsapp.index',
                    'permission' => 'settings.whatsapp.manage',
                ],
                [
                    'label' => __('Status Server'),
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
                    <li class="menu-item hs-accordion">
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
                    <li class="menu-item">
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
