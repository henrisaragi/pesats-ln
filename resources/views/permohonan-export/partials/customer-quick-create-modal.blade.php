{{--
    Modal bersama (satu instance untuk seluruh halaman) yang dipicu tombol "+" di sebelah select
    Customer pada modal Tambah/Ubah SATS-LN. Submit-nya murni lewat AJAX (resources/js/pages/
    permohonan-show.js) - bukan full-page POST - supaya setelah tersimpan bisa langsung kembali ke
    modal SATS-LN yang membukanya dengan Customer baru otomatis terpilih (lihat data-satsln-add-
    customer/data-target-select/data-return-modal pada tombol "+" di sats-ln-modal-form.blade.php).
    Error validasi ditampilkan lewat <p data-error="..."> yang diisi JS dari respons JSON 422.
--}}
<div aria-labelledby="customer-quick-create-modal-label" id="customer-quick-create-modal"
     class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
     role="dialog" tabindex="-1">
    <div
        class="hs-overlay-animation-target hs-overlay-open:scale-100 hs-overlay-open:opacity-100 scale-95 opacity-0 ease-in-out transition-all duration-200 sm:max-w-lg sm:w-full m-3 sm:mx-auto min-h-[calc(100%-56px)] flex items-center">
        <div class="w-full flex flex-col card border border-default-200 shadow-2xs rounded-xl pointer-events-auto">
            <div class="card-header">
                <h3 class="font-bold text-default-800 text-base" id="customer-quick-create-modal-label">
                    {{ __('Tambah Customer') }}
                </h3>
                <button aria-label="{{ __('Tutup') }}" class="size-5 text-default-800"
                        data-hs-overlay="#customer-quick-create-modal" type="button">
                    <span class="sr-only">{{ __('Tutup') }}</span>
                    <i class="size-5" data-lucide="x"></i>
                </button>
            </div>
            <form action="{{ route('customer.store') }}" data-satsln-quick-customer-form method="POST">
                @csrf
                <p class="hidden mx-4 mt-4 text-xs text-danger" data-error="general"
                   data-message="{{ __('Terjadi kesalahan, silakan coba lagi.') }}"></p>
                <div class="p-4 flex flex-col gap-y-4">
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="satsln-quick-customer-name">
                            {{ __('Nama Customer') }}
                        </label>
                        <input class="form-input" id="satsln-quick-customer-name" name="name" type="text"/>
                        <p class="hidden mt-1 text-xs text-danger" data-error="name"></p>
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="satsln-quick-customer-country">
                            {{ __('Negara') }}
                        </label>
                        <x-country-select id="satsln-quick-customer-country" name="country_id"/>
                        <p class="hidden mt-1 text-xs text-danger" data-error="country_id"></p>
                    </div>
                    <div>
                        <label class="inline-block mb-2 text-sm font-medium" for="satsln-quick-customer-address">
                            {{ __('Alamat') }}
                        </label>
                        <textarea class="form-input" id="satsln-quick-customer-address" name="address"
                                  rows="3"></textarea>
                        <p class="hidden mt-1 text-xs text-danger" data-error="address"></p>
                    </div>
                </div>
                <div class="p-4 flex justify-end gap-2 border-t border-dashed border-default-900/10">
                    <button class="btn bg-default-150" data-hs-overlay="#customer-quick-create-modal"
                            type="button">{{ __('Batal') }}</button>
                    <button class="btn bg-primary text-white" type="submit">{{ __('Simpan') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
