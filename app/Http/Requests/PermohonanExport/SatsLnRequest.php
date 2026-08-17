<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Models\Entitas\Entitas;
use App\Models\KuotaPerusahaan;
use App\Services\PermohonanService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SatsLnRequest extends FormRequest
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
            'customer_company_id' => ['nullable', 'integer', 'exists:customer_companies,id'],
            'country_of_exportation' => ['nullable', 'integer', 'exists:countries,id'],
            'country_of_destination' => ['nullable', 'integer', 'exists:countries,id'],
            'port_of_exportation' => ['nullable', 'integer', 'exists:ports,id'],
            'port_of_destination' => ['nullable', 'integer', 'exists:ports,id'],
            'purpose_of_transaction_id' => ['required', 'integer', 'exists:purpose_of_transactions,id'],
            'valid_until' => ['nullable', 'date'],
            'special_conditions' => ['nullable', 'string'],
            'kuota_perusahaan_id' => ['nullable', 'integer', 'exists:kuota_perusahaan,id'],
            'qty' => ['nullable', 'numeric', 'min:0.01'],
            'peb_or_pib' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('kuota_perusahaan_id')) {
                return;
            }

            // Kuota/qty (spesies) belum wajib diisi sejak modal "header" SATS-LN (Revisi Permohonan
            // 3) - logikanya bakal jadi task terpisah nanti. Kalau memang tidak dikirim, tidak ada
            // yang perlu divalidasi di sini.
            if ($this->input('kuota_perusahaan_id') === null || $this->input('kuota_perusahaan_id') === '') {
                return;
            }

            /** @var Entitas $entitas */
            $entitas = Auth::user()->entity;

            $kuotaPerusahaan = KuotaPerusahaan::with('kuotaNasional')->find($this->integer('kuota_perusahaan_id'));

            if ($kuotaPerusahaan === null || $kuotaPerusahaan->entity_id !== $entitas->id) {
                $validator->errors()->add(
                    'kuota_perusahaan_id',
                    __('Kuota perusahaan tidak valid untuk entitas ini.')
                );

                return;
            }

            $exceptItemId = $this->route('satsLn')?->items()->value('id');
            $sisaPerusahaan = $this->permohonanService->sisaKuotaPerusahaan($kuotaPerusahaan, $exceptItemId);

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
            $sisaNasional = $this->permohonanService->sisaKuotaNasional($kuotaPerusahaan->kuotaNasional, $exceptItemId);

            if ($this->float('qty') > $sisaNasional) {
                $validator->errors()->add(
                    'qty',
                    __('Jumlah melebihi sisa kuota nasional yang tersedia (:sisa).', ['sisa' => number_format($sisaNasional, 2, ',', '.')])
                );
            }
        });
    }
}
