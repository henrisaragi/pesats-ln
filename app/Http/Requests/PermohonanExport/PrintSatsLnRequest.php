<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Models\SatsLnPrint;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrintSatsLnRequest extends FormRequest
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
            'penandatangan_id' => ['required', 'integer', Rule::in(User::penandatangan()->pluck('id'))],
            'sisa_tinggi' => ['nullable', 'numeric', 'min:0', 'max:'.SatsLnPrint::MAX_SISA_TINGGI],
        ];
    }

    public function penandatangan(): User
    {
        return User::findOrFail($this->integer('penandatangan_id'));
    }

    /**
     * Margin (cm) = tinggi baris kosong terakhir tabel spesies.
     */
    public function sisaTinggi(): float
    {
        return $this->filled('sisa_tinggi')
            ? (float) $this->input('sisa_tinggi')
            : SatsLnPrint::DEFAULT_SISA_TINGGI;
    }
}
