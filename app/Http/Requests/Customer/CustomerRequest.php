<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Data customer harus ketat per entitas (lihat claude/customer-task.md) - entity_id
        // selalu dipaksa ke entitas milik user login, tidak pernah bisa dipilih bebas lewat form
        // atau diakali lewat POST langsung, terlepas dari permission apa pun yang dimiliki user.
        $this->merge(['entity_id' => $this->user()?->entity_id]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entity_id' => ['required', 'integer', 'exists:entities,id'],
            'name' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
