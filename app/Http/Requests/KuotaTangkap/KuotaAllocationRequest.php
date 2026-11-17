<?php

declare(strict_types=1);

namespace App\Http\Requests\KuotaTangkap;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi bentuk input. Aturan bisnis (alasan wajib bila menyimpang, Σ ditetapkan ≤ Q) diperiksa
 * di KuotaPeriodWorkflowService di dalam transaksi terkunci, karena juga berlaku saat Ajukan.
 */
class KuotaAllocationRequest extends FormRequest
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
            'allocations' => ['required', 'array'],
            'allocations.*.ditetapkan' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'allocations.*.alasan_penyesuaian' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, array{ditetapkan: ?string, alasan_penyesuaian: ?string}>
     */
    public function allocationInput(): array
    {
        $input = [];

        foreach ((array) $this->validated('allocations') as $id => $row) {
            $input[(int) $id] = [
                'ditetapkan' => isset($row['ditetapkan']) ? (string) $row['ditetapkan'] : null,
                'alasan_penyesuaian' => isset($row['alasan_penyesuaian']) ? (string) $row['alasan_penyesuaian'] : null,
            ];
        }

        return $input;
    }
}
