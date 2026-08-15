<?php

declare(strict_types=1);

namespace App\Http\Requests\KuotaPerusahaan;

use App\Models\Entitas\Entitas;
use App\Services\KuotaPerusahaanService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class KuotaPerusahaanRequest extends FormRequest
{
    public function __construct(private readonly KuotaPerusahaanService $kuotaPerusahaanService)
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
            'entity_id' => ['required', 'integer', 'exists:entities,id'],
            'komoditas_id' => ['required', 'integer', 'exists:komoditas,id'],
            'source_id' => ['required', 'integer', 'exists:source_statuses,id'],
            'trade_code_id' => ['required', 'integer', 'exists:trade_codes,id'],
            'species_id' => ['required', 'integer', 'exists:species,id'],
            'year' => ['required', 'integer', 'digits:4'],
            'quota' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $entitas = Entitas::find($this->integer('entity_id'));

            if ($entitas === null) {
                return;
            }

            if (! $this->kuotaPerusahaanService->entitasBisaMengajukan($entitas, $this->integer('komoditas_id'))) {
                $validator->errors()->add(
                    'komoditas_id',
                    __('Komoditas ini belum aktif/terverifikasi untuk entitas terpilih.')
                );

                return;
            }

            $kuotaNasional = $this->kuotaPerusahaanService->resolveKuotaNasional(
                $this->integer('komoditas_id'),
                $this->integer('source_id'),
                $this->integer('trade_code_id'),
                $this->integer('species_id'),
                $this->integer('year'),
            );

            if ($kuotaNasional === null) {
                $validator->errors()->add(
                    'species_id',
                    __('Kuota Nasional tidak ditemukan untuk kombinasi status asal, kode perdagangan, dan spesies ini.')
                );

                return;
            }

            $exceptId = $this->route('kuotaPerusahaan')?->id;
            $sisa = $this->kuotaPerusahaanService->sisaKuotaNasional($kuotaNasional, $exceptId);

            if ($this->float('quota') > $sisa) {
                $validator->errors()->add(
                    'quota',
                    __('Kuota melebihi sisa kuota nasional yang tersedia (:sisa).', ['sisa' => number_format($sisa, 2, ',', '.')])
                );
            }
        });
    }
}
