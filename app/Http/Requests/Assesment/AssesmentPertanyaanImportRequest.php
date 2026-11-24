<?php

declare(strict_types=1);

namespace App\Http\Requests\Assesment;

use App\Models\Komoditas;
use App\Services\Assesment\AssesmentPertanyaanTransferService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
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
     * file lolos validasi dasar. Aturannya mengikuti AssesmentPertanyaanRequest.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = json_decode((string) $this->file('file')->get(), true);

            if (! is_array($data) || ($data['format'] ?? null) !== AssesmentPertanyaanTransferService::FORMAT) {
                $validator->errors()->add('file', __('File bukan hasil export pertanyaan assesment yang valid.'));

                return;
            }

            $codes = Komoditas::query()->pluck('code')->all();

            $content = ValidatorFactory::make($data, [
                'pertanyaan' => ['required', 'array', 'min:1'],
                'pertanyaan.*.aspek' => ['required', 'string', 'max:255'],
                'pertanyaan.*.pertanyaan' => ['required', 'string'],
                'pertanyaan.*.informasi' => ['nullable', 'string'],
                'pertanyaan.*.metode_verifikasi' => ['nullable', 'string'],
                'pertanyaan.*.komoditas' => ['required', 'array', 'min:1'],
                'pertanyaan.*.komoditas.*.code' => ['required', 'string', 'distinct', Rule::in($codes)],
                'pertanyaan.*.komoditas.*.bobot' => ['required', 'numeric', 'min:0', 'max:100'],
                'pertanyaan.*.jawaban' => ['required', 'array', 'min:2'],
                'pertanyaan.*.jawaban.*.teks' => ['required', 'string', 'max:255'],
                'pertanyaan.*.jawaban.*.skor' => ['required', 'array'],
                'pertanyaan.*.jawaban.*.skor.*' => ['required', 'numeric'],
                'pertanyaan.*.bukti_dukung' => ['required', 'array', 'min:1'],
                'pertanyaan.*.bukti_dukung.*' => ['required', 'string', 'max:255'],
            ], [
                'pertanyaan.*.komoditas.*.code.in' => __('Kode komoditas ":input" tidak ditemukan di sistem.'),
            ]);

            if ($content->fails()) {
                $validator->errors()->add('file', $content->errors()->first());

                return;
            }

            foreach ($data['pertanyaan'] as $index => $item) {
                $komoditasCodes = collect($item['komoditas'])->pluck('code')->map(fn (mixed $code): string => (string) $code)->sort()->values()->all();

                foreach ($item['jawaban'] as $jawaban) {
                    $skorCodes = collect(array_keys($jawaban['skor']))->map(fn (int|string $code): string => (string) $code)->sort()->values()->all();

                    if ($skorCodes !== $komoditasCodes) {
                        $validator->errors()->add('file', __('Pertanyaan ke-:nomor: skor setiap jawaban harus diisi tepat untuk komoditas pertanyaan tersebut.', ['nomor' => $index + 1]));

                        return;
                    }
                }
            }

            $this->items = array_values($data['pertanyaan']);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pertanyaanItems(): array
    {
        return $this->items;
    }
}
