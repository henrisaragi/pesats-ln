<?php

declare(strict_types=1);

namespace App\Http\Requests\Entitas;

use App\Models\Entitas\Entitas;
use App\Services\Entitas\EntityProfileService;
use Illuminate\Foundation\Http\FormRequest;

class EntityProfileRequest extends FormRequest
{
    public function __construct(private readonly EntityProfileService $profileService)
    {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Entitas $entity */
        $entity = $this->route('entity');

        return $this->profileService->rules($entity);
    }
}
