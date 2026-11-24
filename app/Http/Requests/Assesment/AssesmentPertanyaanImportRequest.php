<?php

declare(strict_types=1);

namespace App\Http\Requests\Assesment;

use App\Models\Komoditas;
use App\Services\Assesment\AssesmentPertanyaanTransferService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssesmentPertanyaanImportRequest extends FormRequest
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $items = [];

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
            'file' => ['required', 'file', 'extensions:json', 'max:2048'],
        ];
    }

    /**
     * Isi file JSON divalidasi di sini (bukan rules()) karena baru bisa dibaca setelah
     * file lolos validasi dasar. Aturannya mengikuti AssesmentPertanyaanRequest. SEMUA
     * kesalahan dikumpulkan di key "file", masing-masing diawali lokasinya di file
     * (mis. "Pertanyaan ke-3 "...", komoditas ke-5: ...") supaya mudah diperbaiki.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = json_decode((string) $this->file('file')->get(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $validator->errors()->add('file', __('File bukan JSON yang valid (:error).', ['error' => json_last_error_msg()]));

                return;
            }

            if (! is_array($data) || ($data['format'] ?? null) !== AssesmentPertanyaanTransferService::FORMAT) {
                $validator->errors()->add('file', __('File bukan hasil export pertanyaan assesment yang valid.'));

                return;
            }

            $messages = [
                ...$this->contentErrors($data),
                ...$this->skorErrors($data),
            ];

            foreach ($messages as $message) {
                $validator->errors()->add('file', $message);
            }

            if ($messages === []) {
                $this->items = array_values($data['pertanyaan']);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function contentErrors(array $data): array
    {
        $content = ValidatorFactory::make($data, [
            'pertanyaan' => ['required', 'array', 'min:1'],
            'pertanyaan.*.aspek' => ['required', 'string', 'max:255'],
            'pertanyaan.*.pertanyaan' => ['required', 'string'],
            'pertanyaan.*.informasi' => ['nullable', 'string'],
            'pertanyaan.*.metode_verifikasi' => ['nullable', 'string'],
            'pertanyaan.*.komoditas' => ['required', 'array', 'min:1'],
            'pertanyaan.*.komoditas.*.code' => ['required', 'string', Rule::in(Komoditas::query()->pluck('code')->all())],
            'pertanyaan.*.komoditas.*.bobot' => ['required', 'numeric', 'min:0', 'max:100'],
            'pertanyaan.*.jawaban' => ['required', 'array', 'min:2'],
            'pertanyaan.*.jawaban.*.teks' => ['required', 'string', 'max:255'],
            'pertanyaan.*.jawaban.*.skor' => ['required', 'array'],
            'pertanyaan.*.jawaban.*.skor.*' => ['required', 'numeric'],
            'pertanyaan.*.bukti_dukung' => ['required', 'array', 'min:1'],
            'pertanyaan.*.bukti_dukung.*' => ['required', 'string', 'max:255'],
        ], [
            'pertanyaan.*.komoditas.*.code.in' => __('Kode komoditas ":input" tidak ditemukan di master data komoditas.'),
        ], [
            'pertanyaan' => __('daftar pertanyaan'),
            'pertanyaan.*.aspek' => __('aspek'),
            'pertanyaan.*.pertanyaan' => __('teks pertanyaan'),
            'pertanyaan.*.informasi' => __('informasi'),
            'pertanyaan.*.metode_verifikasi' => __('metode verifikasi'),
            'pertanyaan.*.komoditas' => __('komoditas'),
            'pertanyaan.*.komoditas.*.code' => __('kode komoditas'),
            'pertanyaan.*.komoditas.*.bobot' => __('bobot'),
            'pertanyaan.*.jawaban' => __('jawaban'),
            'pertanyaan.*.jawaban.*.teks' => __('teks jawaban'),
            'pertanyaan.*.jawaban.*.skor' => __('skor'),
            'pertanyaan.*.jawaban.*.skor.*' => __('skor'),
            'pertanyaan.*.bukti_dukung' => __('bukti dukung'),
            'pertanyaan.*.bukti_dukung.*' => __('bukti dukung'),
        ]);

        $result = [];

        foreach ($content->errors()->messages() as $path => $pathMessages) {
            foreach ($pathMessages as $message) {
                $result[] = $this->locate($data, $path, $message);
            }
        }

        return $result;
    }

    /**
     * Kode komoditas tidak boleh dobel dalam satu pertanyaan, dan skor tiap jawaban wajib ada
     * tepat untuk komoditas pertanyaannya (tidak kurang, tidak lebih).
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function skorErrors(array $data): array
    {
        $result = [];

        foreach ((array) ($data['pertanyaan'] ?? []) as $index => $item) {
            if (! is_array($item) || ! is_array($item['komoditas'] ?? null) || ! is_array($item['jawaban'] ?? null)) {
                continue;
            }

            $komoditasCodes = collect($item['komoditas'])
                ->map(fn (mixed $row): string => is_array($row) ? (string) ($row['code'] ?? '') : '')
                ->filter()
                ->all();

            // Bukan rule "distinct" - di path ber-wildcard ganda rule itu membandingkan kode
            // di SEMUA pertanyaan sekaligus, padahal komoditas yang sama wajar dipakai berulang.
            if ($duplicates = array_unique(array_diff_assoc($komoditasCodes, array_unique($komoditasCodes)))) {
                $result[] = $this->locate($data, "pertanyaan.{$index}", __('Kode komoditas :codes tercantum lebih dari sekali.', ['codes' => implode(', ', $duplicates)]));
            }

            $komoditasCodes = array_values(array_unique($komoditasCodes));

            foreach ($item['jawaban'] as $jawabanIndex => $jawaban) {
                if (! is_array($jawaban) || ! is_array($jawaban['skor'] ?? null)) {
                    continue;
                }

                $skorCodes = array_map('strval', array_keys($jawaban['skor']));
                $path = "pertanyaan.{$index}.jawaban.{$jawabanIndex}";

                if ($missing = array_diff($komoditasCodes, $skorCodes)) {
                    $result[] = $this->locate($data, $path, __('Skor untuk komoditas :codes belum diisi.', ['codes' => implode(', ', $missing)]));
                }

                if ($extra = array_diff($skorCodes, $komoditasCodes)) {
                    $result[] = $this->locate($data, $path, __('Skor untuk komoditas :codes tidak termasuk komoditas pertanyaan ini.', ['codes' => implode(', ', $extra)]));
                }
            }
        }

        return $result;
    }

    /**
     * Mengubah path validasi (mis. "pertanyaan.0.komoditas.4.code") jadi lokasi yang mudah
     * dibaca: "Pertanyaan ke-1 "Tingkat pemanfaatan...", komoditas ke-5: <pesan>".
     *
     * @param  array<string, mixed>  $data
     */
    private function locate(array $data, string $path, string $message): string
    {
        $segments = explode('.', $path);

        if (count($segments) < 2 || $segments[0] !== 'pertanyaan') {
            return $message;
        }

        $index = $segments[1];
        $teks = $data['pertanyaan'][$index]['pertanyaan'] ?? null;
        $location = __('Pertanyaan ke-:nomor', ['nomor' => is_numeric($index) ? (int) $index + 1 : $index]);

        if (is_string($teks) && $teks !== '') {
            $location .= ' "'.Str::limit($teks, 50).'"';
        }

        $field = $segments[2] ?? null;
        $subIndex = $segments[3] ?? null;

        if (is_numeric($subIndex)) {
            $nomor = (int) $subIndex + 1;
            $location .= match ($field) {
                'komoditas' => ', '.__('komoditas ke-:nomor', ['nomor' => $nomor]),
                'jawaban' => ', '.__('jawaban ke-:nomor', ['nomor' => $nomor]),
                'bukti_dukung' => ', '.__('bukti dukung ke-:nomor', ['nomor' => $nomor]),
                default => '',
            };
        }

        return $location.': '.$message;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pertanyaanItems(): array
    {
        return $this->items;
    }
}
