<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Models\Entitas\Entitas;
use App\Services\PermohonanService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SatsLnItemRequest extends FormRequest
{
    public function __construct(private readonly PermohonanService $permohonanService)
    {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_id' => ['required', 'integer', 'exists:source_statuses,id'],
            'komoditas_id' => ['required', 'integer', 'exists:komoditas,id'],
            'trade_code_id' => ['required', 'integer', 'exists:trade_codes,id'],
            'species_id' => ['required', 'integer', 'exists:species,id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Entitas $entitas */
            $entitas = Auth::user()->entity;

            $kuotaPerusahaan = $this->permohonanService->resolveKuotaPerusahaanForEntitas(
                $entitas,
                $this->integer('source_id'),
                $this->integer('komoditas_id'),
                $this->integer('trade_code_id'),
                $this->integer('species_id'),
            );

            if ($kuotaPerusahaan === null) {
                $validator->errors()->add(
                    'species_id',
                    __('Kombinasi Status Asal, Komoditas, Trade Code, dan Species ini tidak ada di kuota perusahaan Anda.')
                );

                return;
            }

            // Item yang sedang diedit (null kalau ini create) - dikecualikan dari pengecekan
            // komoditas-konsisten & sisa kuota di bawah, supaya edit tidak "diblokir" oleh
            // konsumsi kuota/komoditas milik dirinya sendiri.
            $editingItem = $this->route('item');

            // Semua spesies dalam satu SATS-LN harus komoditas yang sama.
            $existingKomoditasId = $this->route('satsLn')?->items()
                ->when($editingItem, fn ($query) => $query->where('id', '!=', $editingItem->id))
                ->with('kuotaNasional:id,komoditas_id')
                ->get()
                ->pluck('kuotaNasional.komoditas_id')
                ->unique()
                ->first();

            if ($existingKomoditasId !== null && (int) $existingKomoditasId !== $this->integer('komoditas_id')) {
                $validator->errors()->add(
                    'komoditas_id',
                    __('Semua spesies dalam satu SATS-LN harus memiliki komoditas yang sama.')
                );

                return;
            }

            $sisaPerusahaan = $this->permohonanService->sisaKuotaPerusahaan($kuotaPerusahaan, $editingItem?->id);

            if ($this->float('qty') > $sisaPerusahaan) {
                $validator->errors()->add(
                    'qty',
                    __('Jumlah melebihi sisa kuota perusahaan yang tersedia (:sisa).', ['sisa' => number_format($sisaPerusahaan, 2, ',', '.')])
                );

                return;
            }

            // Sisa kuota perusahaan cukup bukan berarti sisa kuota nasional (bersama entitas
            // lain yang memakai kuota_nasional yang sama) juga masih cukup - lihat Revisi
            // Permohonan 4 di claude/permohonan-task.md.
            $sisaNasional = $this->permohonanService->sisaKuotaNasional($kuotaPerusahaan->kuotaNasional, $editingItem?->id);

            if ($this->float('qty') > $sisaNasional) {
                $validator->errors()->add(
                    'qty',
                    __('Jumlah melebihi sisa kuota nasional yang tersedia (:sisa).', ['sisa' => number_format($sisaNasional, 2, ',', '.')])
                );
            }
        });
    }
}
