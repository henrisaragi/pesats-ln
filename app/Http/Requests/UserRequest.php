<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => str_replace(' ', '', (string) $this->input('username')),
            ]);
        }

        $actor = $this->user();

        // Non-super-admins can only manage users within their own entity and role,
        // regardless of what the (visually locked) form fields actually submitted.
        if ($actor && ! $actor->hasRole('super-admin')) {
            $this->merge([
                'entity_id' => $actor->entity_id,
                'role' => $actor->roles->first()?->name,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'min:8'],
            'entity_id' => ['nullable', 'integer', 'exists:entities,id'],
            'nip' => ['nullable', 'string', 'max:50'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ];
    }
}
