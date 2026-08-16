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
            'kuota_perusahaan_id' => ['required', 'integer', 'exists:kuota_perusahaan,id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
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

            /** @var Entitas $entitas */
            $entitas = Auth::user()->entity;

            $kuotaPerusahaan = KuotaPerusahaan::find($this->integer('kuota_perusahaan_id'));

            if ($kuotaPerusahaan === null || $kuotaPerusahaan->entity_id !== $entitas->id) {
                $validator->errors()->add(
                    'kuota_perusahaan_id',
                    __('Kuota perusahaan tidak valid untuk entitas ini.')
                );

                return;
            }

            $exceptItemId = $this->route('satsLn')?->items()->value('id');
            $sisa = $this->permohonanService->sisaKuotaPerusahaan($kuotaPerusahaan, $exceptItemId);

            if ($this->float('qty') > $sisa) {
                $validator->errors()->add(
                    'qty',
                    __('Jumlah melebihi sisa kuota perusahaan yang tersedia (:sisa).', ['sisa' => number_format($sisa, 2, ',', '.')])
                );
            }
        });
    }
}
