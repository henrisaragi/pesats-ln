{{-- Satu baris jawaban + skor per komoditas. $index = int atau "__INDEX__" (template repeatable-rows.js). --}}
<div class="border border-default-200 rounded-lg p-3 mb-3" data-repeatable-row data-jawaban-row>
    <div class="flex items-center gap-2 mb-2">
        <input class="form-input grow" name="jawaban[{{ $index }}][teks]"
               placeholder="{{ __('Teks Jawaban') }}" type="text" value="{{ $row['teks'] ?? '' }}">
        <button aria-label="{{ __('Hapus') }}" class="btn btn-icon size-8 bg-default-150 text-danger"
                data-repeatable-remove type="button">
            <i class="size-4" data-lucide="trash-2"></i>
        </button>
    </div>
    <label class="inline-flex items-center gap-1.5 text-xs text-default-600 mb-2">
        <input class="form-checkbox" data-skor-sama-toggle type="checkbox">
        {{ __('Skor sama untuk semua komoditas tercentang') }}
    </label>
    <div class="grid sm:grid-cols-2 gap-2" data-skor-fields>
        @foreach ($komoditasOptions as $komoditas)
            <div class="flex items-center gap-2 hidden" data-komoditas-id="{{ $komoditas->id }}" data-skor-field>
                <span class="text-xs text-default-500 w-28 truncate">{{ $komoditas->name }}</span>
                <input class="form-input form-input-sm" data-komoditas-id="{{ $komoditas->id }}"
                       data-skor-input name="jawaban[{{ $index }}][skor][{{ $komoditas->id }}]"
                       placeholder="{{ __('Skor') }}" step="0.01" type="number"
                       value="{{ $row['skor'][$komoditas->id] ?? '' }}">
            </div>
        @endforeach
    </div>
</div>
