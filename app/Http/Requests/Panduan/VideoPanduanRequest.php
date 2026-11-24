<?php

declare(strict_types=1);

namespace App\Http\Requests\Panduan;

use App\Enums\FiturPanduan;
use App\Models\Panduan\VideoPanduan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VideoPanduanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'fitur' => ['required', 'array', 'min:1'],
            'fitur.*' => ['required', 'distinct', Rule::enum(FiturPanduan::class)],
            'is_active' => ['boolean'],
            'video_url' => ['nullable', 'url:http,https', 'max:500'],
            'file' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:102400'],
        ];
    }

    /**
     * Wajib ada salah satu sumber video: link baru, file baru, atau (saat ubah) file lama.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $video = $this->route('video');
                $hasExistingFile = $video instanceof VideoPanduan && $video->file_path !== null;

                if (! $this->filled('video_url') && ! $this->hasFile('file') && ! $hasExistingFile) {
                    $validator->errors()->add('video_url', __('Isi link video atau upload file video.'));
                }
            },
        ];
    }
}
