<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DisposisiRequest extends FormRequest
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
            'verifikator_id' => ['required', 'integer', Rule::in(
                User::whereHas('roles', fn ($query) => $query->where('name', 'verifikator'))
                    ->where('is_active', true)
                    ->pluck('id')
            )],
        ];
    }
}
