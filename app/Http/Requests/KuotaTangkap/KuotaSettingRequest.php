<?php

declare(strict_types=1);

namespace App\Http\Requests\KuotaTangkap;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Input dalam persen (30, 70, 10); disimpan sebagai pecahan oleh controller.
 */
class KuotaSettingRequest extends FormRequest
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
        return [
            'bobot_ekspor' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'bobot_evaluasi' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'cadangan' => ['required', 'numeric', 'between:0,50', 'decimal:0,2'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (bccomp(bcadd((string) $this->input('bobot_ekspor'), (string) $this->input('bobot_evaluasi'), 2), '100', 2) !== 0) {
                $validator->errors()->add('bobot_evaluasi', __('Jumlah bobot ekspor dan bobot evaluasi harus 100%.'));
            }
        });
    }

    /**
     * @return array{bobot_ekspor: string, bobot_evaluasi: string, cadangan: string}
     */
    public function fractions(): array
    {
        return [
            'bobot_ekspor' => bcdiv((string) $this->validated('bobot_ekspor'), '100', 4),
            'bobot_evaluasi' => bcdiv((string) $this->validated('bobot_evaluasi'), '100', 4),
            'cadangan' => bcdiv((string) $this->validated('cadangan'), '100', 4),
        ];
    }
}
