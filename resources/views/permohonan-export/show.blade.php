@extends('layouts.vertical', ['title' => __('Permohonan SATS-LN Ekspor')])

@section('css')
@endsection

@section('content')
    @include('layouts.partials/page-title', [
        'subtitle' => __('Layanan Entitas'),
        'title' => $permohonan->nomor_permohonan,
    ])

    <div class="grid lg:grid-cols-4 grid-cols-1 gap-5">
        <div class="lg:col-span-1 col-span-1 card print:hidden flex flex-col">
            <div class="sticky top-24 flex flex-col h-full">
                <div class="card-body shrink-0">
                    <h6 class="mb-4 card-title">{{ __('Daftar SATS-LN') }}</h6>
                    <div class="flex items-center gap-2">
                        <div class="relative w-full">
                            <input class="ps-11 form-input form-input-sm" placeholder="{{ __('Cari...') }}" type="text" />
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3">
                                <i class="size-4 flex items-center fill-default-100 text-default-500"
                                    data-lucide="search"></i>
                            </div>
                        </div>
                        @can('permohonan-export.edit')
                            @if ($permohonan->status->isEditable())
                                <a class="btn size-7.5 bg-primary text-white shrink-0"
                                    href="{{ route('permohonan-export.edit', $permohonan) }}"
                                    aria-label="{{ __('Tambah SATS-LN') }}">
                                    <i class="size-4" data-lucide="plus"></i>
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="overflow-y-auto flex-1" data-simplebar="">
                    @forelse ($permohonan->satsLn as $satsLn)
                        @php
                            $isSigned = $satsLn->signed_at !== null;
                            $isPrinted = $satsLn->printed_at !== null;
                            $satsLnStatusLabel = $isSigned
                                ? __('Ditandatangani')
                                : ($isPrinted
                                    ? __('Dicetak')
                                    : __('Menunggu Verifikasi'));
                            $satsLnStatusClass = $isSigned
                                ? 'inline-flex items-center gap-x-1.5 py-0.5 px-2.5 rounded text-xs font-medium bg-success/10 text-success'
                                : ($isPrinted
                                    ? 'inline-flex items-center gap-x-1.5 py-0.5 px-2.5 rounded text-xs font-medium bg-primary/10 text-primary'
                                    : 'text-warning bg-warning/20 rounded text-xs py-0.5 px-2.5');
                        @endphp
                        <div class="block border-t p-5 border-default-200">
                            <div class="flex justify-between">
                                <div class="w-full">
                                    <h6 class="font-semibold text-sm text-default-700">
                                        {{ __('SATS-LN Ke #:nomor', ['nomor' => $loop->iteration]) }}
                                    </h6>
                                    <div>
                                        <div class="flex items-center justify-between gap-6 mt-3">
                                            <button class="inline-flex text-sm items-center" type="button">
                                                <i class="size-4 text-default-500 me-1.5" data-lucide="calendar-clock"></i>
                                                {{ $satsLn->publish_date?->translatedFormat('d M Y') ?? __('Menunggu...') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="{{ $satsLnStatusClass }}">{{ $satsLnStatusLabel }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="p-5 text-sm text-default-500">{{ __('Belum ada SATS-LN.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="lg:col-span-3 col-span-1">
            <div class="card">
                <div class="card-body">
                    <div class="flex flex-wrap justify-between items-center md:gap-0 gap-4">
                        <div>
                            <h6 class="mb-1 text-base font-semibold text-default-800">#TW15090257</h6>
                            <ul class="flex items-center gap-3 text-default-500 text-sm">
                                <li>Create: 10 July, 2023</li>
                                <li>Due: 10 July, 2023</li>
                            </ul>
                        </div>
                        <div class="flex gap-2">
                            <button class="btn bg-primary text-white" onclick="window.print()"><i
                                    class="size-4 text-white me-1" data-lucide="save"></i> Save &amp; Draft
                            </button>
                            <div class="hs-dropdown relative inline-flex">
                                <button aria-expanded="false" aria-haspopup="menu" aria-label="Dropdown"
                                    class="hs-dropdown-toggle btn size-9.5 bg-default-100 hover:bg-default-600 text-default-500 hover:text-white"
                                    hs-dropdown-placement="bottom-end" type="button">
                                    <i class="iconify lucide--ellipsis size-4"></i>
                                </button>
                                <div class="hs-dropdown-menu" role="menu">
                                    <div>
                                        <a class="flex items-center gap-1.5 py-1.5 font-medium px-3 text-sm text-default-500 hover:bg-default-150 rounded"
                                            href="#">
                                            <i class="size-3" data-lucide="edit"></i>
                                            Edit
                                        </a>
                                        <a class="flex items-center gap-1.5 py-1.5 font-medium px-3 text-sm text-default-500 hover:bg-default-150 rounded"
                                            href="#">
                                            <i class="size-3" data-lucide="trash-2"></i>
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="card-body">
                        <div class="grid lg:grid-cols-12 items-center gap-4">
                            <div class="lg:col-span-3 text-start">
                                <div class="size-16 bg-default-100 flex items-center justify-center rounded-md">
                                    <img alt="" class="h-8" src="/images/logo-sm.png" />
                                </div>
                                <h5 class="mt-4 mb-1 text-lg font-semibold text-default-800">Themesdesign</h5>
                                <p class="text-default-500">IT Software Company</p>
                            </div>
                            <div class="lg:col-span-9 lg:col-start-12 lg:text-end text-start text-sm">
                                <p class="mb-1 text-default-500">Legal Registration No: <span
                                        class="font-semibold">2155420</span>
                                </p>
                                <p class="mb-1 truncate text-default-500">Email: <span
                                        class="font-semibold">tailwick@themesdesign.in</span>
                                </p>
                                <p class="mb-1 text-default-500">Website: <a class="font-semibold underline text-primary"
                                        href="https://themesdesign.in" target="_blank">www.themesdesign.in</a>
                                </p>
                                <p class="text-default-500">Contact No: <span class="font-semibold">+(01) 123
                                        678 9654</span>
                                </p>
                            </div>
                        </div>
                        <div
                            class="grid md:grid-cols-4 grid-cols-1 mt-6 text-center md:divide-x divide-dashed divide-default-200">
                            <div class="p-3 text-sm">
                                <h6 class="mb-1 font-semibold text-default-800">#TW15090254</h6>
                                <p class="text-default-500">Invoice No</p>
                            </div>
                            <div class="p-3 text-sm">
                                <h6 class="mb-1 font-semibold text-default-800">10 July, 2023</h6>
                                <p class="text-default-500">Create Date</p>
                            </div>
                            <div class="p-3 test-sm">
                                <span
                                    class="inline-flex items-center gap-x-1.5 py-0.5 px-2.5 rounded text-xs font-medium bg-success/10 text-success">Paid</span>
                                <p class="text-default-500">Payment Status</p>
                            </div>
                            <div class="p-3 text-sm">
                                <h6 class="mb-1 font-semibold text-default-800">$873.96</h6>
                                <p class="text-default-500">Total Amount</p>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-2 grid-cols-1 gap-5 mt-8">
                            <div class="text-default-500">
                                <p class="mb-2 text-sm uppercase">Shipping Address</p>
                                <h6 class="mb-1 text-base font-semibold text-default-800">Paula Keenan</h6>
                                <p class="mb-1 text-sm">176 Arvid Crest Sheastad, IA</p>
                                <p class="mb-1 text-sm">+(211) 0123 456 897</p>
                            </div>
                            <div class="text-default-500">
                                <p class="mb-2 text-sm uppercase">Billing Address</p>
                                <h6 class="mb-1 text-base font-semibold text-default-800">Paula Keenan</h6>
                                <p class="mb-1 text-sm">176 Arvid Crest Sheastad, IA</p>
                                <p class="mb-1 text-sm">+(211) 0123 456 897</p>
                                <p class="mb-1 text-sm">TAX No. 5415421</p>
                            </div>
                        </div>
                        <div class="mt-8 overflow-x-auto">
                            <table class="w-full whitespace-nowrap">
                                <thead>
                                    <tr class="font-semibold border-b border-default-200 text-default-500 text-sm">
                                        <th class="px-3.5 py-2.5 text-start">#</th>
                                        <th class="px-3.5 py-2.5 text-start">Item Name</th>
                                        <th class="px-3.5 py-2.5 text-start">Rate</th>
                                        <th class="px-3.5 py-2.5 text-start">Quantity</th>
                                        <th class="px-3.5 py-2.5 text-start">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-default-200 text-default-800 text-sm">
                                        <td class="px-3.5 py-2.5">1</td>
                                        <td class="px-3.5 py-2.5">
                                            <h6 class="mb-1 font-medium">Webadmin - Premium Admin &amp; Dashboard</h6>
                                            <p class="text-default-500">Build with Bootstrap, React JS, Angular, Vue
                                                etc.</p>
                                        </td>
                                        <td class="px-3.5 py-2.5">$24.00</td>
                                        <td class="px-3.5 py-2.5">2</td>
                                        <td class="px-3.5 py-2.5">$48</td>
                                    </tr>
                                    <tr class="border-b border-default-200 text-default-800 text-sm">
                                        <td class="px-3.5 py-2.5">2</td>
                                        <td class="px-3.5 py-2.5">
                                            <h6 class="mb-1 font-medium">Webadmin - Admin &amp; Dashboard</h6>
                                            <p class="text-default-500">Build with Bootstrap, React JS, Angular, Vue
                                                etc.</p>
                                        </td>
                                        <td class="px-3.5 py-2.5">$25.00</td>
                                        <td class="px-3.5 py-2.5">4</td>
                                        <td class="px-3.5 py-2.5">$100</td>
                                    </tr>
                                    <tr class="border-b border-default-200 text-default-800 text-sm">
                                        <td class="px-3.5 py-2.5">1</td>
                                        <td class="px-3.5 py-2.5">
                                            <h6 class="mb-1 font-medium">Tocly - Admin &amp; Dashboard</h6>
                                            <p class="text-default-500">Build with Bootstrap, React JS, Angular, Vue
                                                etc.</p>
                                        </td>
                                        <td class="px-3.5 py-2.5">$16.00</td>
                                        <td class="px-3.5 py-2.5">9</td>
                                        <td class="px-3.5 py-2.5">$144</td>
                                    </tr>
                                </tbody>
                                <tbody>
                                    <tr class="text-default-500 text-sm">
                                        <td colspan="3"></td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5">
                                            Sub Total
                                        </td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5">
                                            $292
                                        </td>
                                    </tr>
                                    <tr class="text-default-500 text-sm">
                                        <td colspan="3"></td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5">
                                            Estimated Tax (18%)
                                        </td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5">
                                            $52.56
                                        </td>
                                    </tr>
                                    <tr class="text-default-500 text-sm">
                                        <td colspan="3"></td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5">
                                            Item Discounts (15%)
                                        </td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5">
                                            -$43.8
                                        </td>
                                    </tr>
                                    <tr class="text-default-500 text-sm">
                                        <td colspan="3"></td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5">
                                            Shipping Charge
                                        </td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5">
                                            $29
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"></td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5 font-medium text-sm">
                                            Total Amount
                                        </td>
                                        <td class="border-b border-default-200 px-3.5 py-2.5 font-medium text-sm">
                                            $329.76
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="my-5 text-default-500 text-sm">
                            <p class="mb-2 text-sm uppercase">Payments Details</p>
                            <p class="mb-1">Payment Method: Credit Card</p>
                            <p class="mb-1">Card Holder: Paula Keenan</p>
                            <p class="mb-1">Card Number: xxxx xxxx xxxx 1402</p>
                            <p>Total Amount: $755.96</p>
                        </div>
                        <p class="px-4 py-3 text-sm rounded-lg border border-info/20 text-info bg-info/10">
                            <b>NOTES:</b> All accounts are to be paid within 7 days from receipt of invoice.
                            To be paid by cheque or credit card or direct payment online. If account is not
                            paid within 7 days the credits details supplied as confirmation of work
                            undertaken will be charged the agreed quoted fee noted above.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection
