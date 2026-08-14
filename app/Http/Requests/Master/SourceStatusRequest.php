<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SourceStatusRequest extends FormRequest
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
        $sourceStatusId = $this->route('sourceStatus')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:5',
                Rule::unique('source_statuses', 'code')->ignore($sourceStatusId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
