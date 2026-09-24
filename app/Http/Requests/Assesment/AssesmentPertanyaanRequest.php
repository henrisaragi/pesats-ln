<?php

declare(strict_types=1);

namespace App\Http\Requests\Assesment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AssesmentPertanyaanRequest extends FormRequest
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
            'aspek_nama' => ['required', 'string', 'max:255'],
            'pertanyaan' => ['required', 'string'],
            'informasi' => ['nullable', 'string'],
            'metode_verifikasi' => ['nullable', 'string'],

            'komoditas_id' => ['required', 'array', 'min:1'],
            'komoditas_id.*' => ['integer', 'exists:komoditas,id', 'distinct'],
            // "nullable": field bobot/skor dirender untuk SEMUA komoditas (disembunyikan via CSS,
            // bukan dihapus dari DOM - lihat resources/views/assesment/pertanyaan/index.blade.php),
            // jadi komoditas yang tidak dicentang tetap ikut terkirim sebagai string kosong.
            // withValidator() di bawah yang memastikan komoditas yang DICENTANG wajib terisi.
            'bobot' => ['required', 'array'],
            'bobot.*' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'jawaban' => ['required', 'array', 'min:2'],
            'jawaban.*.teks' => ['required', 'string', 'max:255'],
            'jawaban.*.skor' => ['required', 'array'],
            'jawaban.*.skor.*' => ['nullable', 'numeric'],

            'bukti_dukung' => ['required', 'array', 'min:1'],
            'bukti_dukung.*' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Setiap komoditas yang dicentang wajib punya bobot, dan setiap jawaban wajib punya skor
     * untuk semua komoditas yang sama - array asosiatif "bobot"/"skor" dikunci oleh komoditas_id
     * jadi tidak bisa divalidasi lewat rules() biasa (lihat resources/js/pages/
     * assesment-pertanyaan-form.js untuk bagaimana form ini diisi).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $komoditasIds = array_map('intval', (array) $this->input('komoditas_id', []));
            $bobot = (array) $this->input('bobot', []);
            $jawaban = (array) $this->input('jawaban', []);

            foreach ($komoditasIds as $komoditasId) {
                if (! isset($bobot[$komoditasId]) || $bobot[$komoditasId] === '') {
                    $validator->errors()->add('bobot', __('Bobot untuk setiap komoditas yang dicentang wajib diisi.'));

                    break;
                }
            }

            foreach ($jawaban as $row) {
                $skor = (array) ($row['skor'] ?? []);

                foreach ($komoditasIds as $komoditasId) {
                    if (! isset($skor[$komoditasId]) || $skor[$komoditasId] === '') {
                        // Key generik "jawaban" (bukan "jawaban.{index}.skor") supaya cocok
                        // dengan @error('jawaban') di resources/views/assesment/pertanyaan/index.blade.php.
                        $validator->errors()->add('jawaban', __('Skor untuk setiap komoditas yang dicentang wajib diisi di semua jawaban.'));

                        break 2;
                    }
                }
            }
        });
    }
}
