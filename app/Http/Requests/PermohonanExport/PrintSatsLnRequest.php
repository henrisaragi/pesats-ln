<?php

declare(strict_types=1);

namespace App\Http\Requests\PermohonanExport;

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
        ];
    }

    public function penandatangan(): User
    {
        return User::findOrFail($this->integer('penandatangan_id'));
    }
}
