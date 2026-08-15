<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use App\Services\Entitas\EntityProfileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class OnboardingDetailRequest extends FormRequest
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
        $entity = Auth::user()->entity;

        return $entity ? $this->profileService->rules($entity) : [];
    }
}
