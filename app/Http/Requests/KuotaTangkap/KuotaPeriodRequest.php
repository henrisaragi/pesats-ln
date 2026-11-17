<?php

declare(strict_types=1);

namespace App\Http\Requests\KuotaTangkap;

use App\Models\Species;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KuotaPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $periodId = $this->route('period')?->id;

        return [
            'provinsi_id' => ['required', 'integer', 'exists:provinsi,id'],
            'komoditas_id' => ['required', 'integer', 'exists:komoditas,id'],
            'species_id' => [
                'required', 'integer', 'exists:species,id',
                Rule::unique('kuota_periods', 'species_id')
                    ->where('provinsi_id', $this->integer('provinsi_id'))
                    ->where('trade_code_id', $this->integer('trade_code_id'))
                    ->where('year', $this->integer('year'))
                    ->whereNull('deleted_at')
                    ->ignore($periodId),
            ],
            'trade_code_id' => ['required', 'integer', 'exists:trade_codes,id'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'kuota_tangkap' => ['required', 'numeric', 'gt:0', 'max:99999999999'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'species_id.unique' => __('Periode untuk provinsi, jenis, kode perdagangan, dan tahun ini sudah ada.'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $speciesKomoditasId = Species::query()->whereKey($this->integer('species_id'))->value('komoditas_id');

            if ((int) $speciesKomoditasId !== $this->integer('komoditas_id')) {
                $validator->errors()->add('species_id', __('Jenis tidak termasuk dalam komoditas yang dipilih.'));
            }
        });
    }
}
