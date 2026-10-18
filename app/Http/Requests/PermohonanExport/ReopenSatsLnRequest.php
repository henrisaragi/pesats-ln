<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Enums\PermohonanStatus;
use App\Models\Permohonan;
use App\Models\SatsLn;
use Illuminate\Foundation\Http\FormRequest;

class ReopenSatsLnRequest extends FormRequest
{
    /**
     * Buka ulang verifikasi hanya untuk SATS-LN yang sudah terverifikasi tapi belum dicetak, dan
     * Permohonan masih di tahap verifikasi.
     */
    public function authorize(): bool
    {
        /** @var Permohonan $permohonan */
        $permohonan = $this->route('permohonan');
        /** @var SatsLn $satsLn */
        $satsLn = $this->route('satsLn');

        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);

        return in_array($permohonan->status, [PermohonanStatus::AntrianVerifikasi, PermohonanStatus::DalamProsesVerifikasi], true)
            && $satsLn->canBeReopened();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pesan' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pesan' => __('Pesan'),
        ];
    }
}
