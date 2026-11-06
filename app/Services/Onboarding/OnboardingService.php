<?php

declare(strict_types=1);

namespace App\Services\Onboarding;

use App\Models\User;
use App\Services\Entitas\EntityProfileService;

class OnboardingService
{
    /**
     * Role auto-assigned when onboarding completes, keyed by entity_type. Note
     * upt_ksda (underscore, matches the entities.entity_type enum) maps to the
     * upt-ksda role (hyphen) - the role names predate this feature and were
     * created manually via the Role admin UI, not renamed to match here.
     *
     * @var array<string, string>
     */
    private const ENTITY_TYPE_ROLES = [
        'perusahaan' => 'perusahaan',
        'penangkaran' => 'penangkaran',
        'perorangan' => 'perorangan',
        'upt_ksda' => 'upt-ksda',
    ];

    public function __construct(private readonly EntityProfileService $profileService) {}

    /**
     * Label for the entity name field on the onboarding "Data Entitas" step,
     * worded per entity_type so the user enters the entity's name rather than
     * their own (users.name is the individual's name from registration).
     */
    public function entityNameLabel(?string $entityType): string
    {
        return match ($entityType) {
            'perusahaan' => __('Nama Perusahaan'),
            'penangkaran' => __('Nama Unit Penangkaran'),
            'perorangan' => __('Nama Lengkap Sesuai Identitas Paspor/KTP'),
            'upt_ksda' => __('Nama Lengkap Sesuai Identitas'),
            default => __('Nama Entitas'),
        };
    }

    public function hasSelectedType(User $user): bool
    {
        return $user->entity !== null;
    }

    /**
     * Step 2 (region/address) is considered done once a province is set - the
     * onboarding entitas form requires it even though the column itself is
     * nullable for the admin-side Entitas CRUD.
     */
    public function hasCompletedEntityData(User $user): bool
    {
        return $user->entity !== null && $user->entity->provinsi_id !== null;
    }

    public function hasCompletedDetail(User $user): bool
    {
        return $user->entity !== null && $this->profileService->find($user->entity) !== null;
    }

    /**
     * Assigns the role matching the user's chosen entity_type (Perusahaan,
     * Penangkaran, Perorangan, UPT KSDA), replacing whatever roles they had.
     * A freshly self-registered user has none yet, so this is effectively
     * their first and only role until an admin changes it later.
     */
    public function assignEntityRole(User $user): void
    {
        $role = self::ENTITY_TYPE_ROLES[$user->entity?->entity_type] ?? null;

        if ($role !== null) {
            $user->syncRoles([$role]);
        }
    }

    public function nextIncompleteStepRoute(User $user): string
    {
        if ($user->onboarding_completed) {
            return route('second', ['dashboards', 'index']);
        }

        if (! $this->hasSelectedType($user)) {
            return route('onboarding.type.show');
        }

        if (! $this->hasCompletedEntityData($user)) {
            return route('onboarding.entitas.show');
        }

        if (! $this->hasCompletedDetail($user)) {
            return route('onboarding.detail.show');
        }

        return route('onboarding.complete.show');
    }
}
