<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurposeOfTransactionRequest extends FormRequest
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
        $purposeOfTransactionId = $this->route('purposeOfTransaction')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('purpose_of_transactions', 'code')->ignore($purposeOfTransactionId),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
