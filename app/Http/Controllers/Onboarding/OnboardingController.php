<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\OnboardingDetailRequest;
use App\Http\Requests\Onboarding\OnboardingEntitasRequest;
use App\Http\Requests\Onboarding\OnboardingTypeRequest;
use App\Models\Entitas\Entitas;
use App\Models\User;
use App\Services\Entitas\EntityProfileService;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly EntityProfileService $profileService,
    ) {}

    public function showType(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($redirect = $this->redirectIfAlreadyCompleted($user)) {
            return $redirect;
        }

        return view('onboarding.type', [
            'entity' => $user->entity,
        ]);
    }

    public function storeType(OnboardingTypeRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $entity = $user->entity ?? new Entitas;
        $isNew = ! $entity->exists;

        $entity->entity_type = $request->validated('entity_type');

        if ($isNew) {
            $entity->name = '';
        }

        $entity->save();

        if ($user->entity_id !== $entity->id) {
            $user->update(['entity_id' => $entity->id]);
        }

        return redirect()->route('onboarding.entitas.show');
    }

    public function showEntitas(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($redirect = $this->redirectIfAlreadyCompleted($user)) {
            return $redirect;
        }

        if (! $this->onboardingService->hasSelectedType($user)) {
            return redirect()->route('onboarding.type.show');
        }

        return view('onboarding.entitas', [
            'entity' => $user->entity,
            'nameLabel' => $this->onboardingService->entityNameLabel($user->entity->entity_type),
        ]);
    }

    public function storeEntitas(OnboardingEntitasRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->entity === null) {
            return redirect()->route('onboarding.type.show');
        }

        $user->entity->update($request->validated());

        return redirect()->route('onboarding.detail.show');
    }

    public function showDetail(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($redirect = $this->redirectIfAlreadyCompleted($user)) {
            return $redirect;
        }

        if (! $this->onboardingService->hasCompletedEntityData($user)) {
            return redirect()->route('onboarding.entitas.show');
        }

        return view('onboarding.detail', [
            'entity' => $user->entity,
            'fields' => $this->profileService->fields($user->entity->entity_type),
            'profile' => $this->profileService->find($user->entity),
        ]);
    }

    public function storeDetail(OnboardingDetailRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->onboardingService->hasCompletedEntityData($user)) {
            return redirect()->route('onboarding.entitas.show');
        }

        $this->profileService->save($user->entity, $request->validated());

        return redirect()->route('onboarding.complete.show');
    }

    public function showComplete(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $this->onboardingService->hasCompletedDetail($user)) {
            return redirect()->route('onboarding.detail.show');
        }

        if (! $user->onboarding_completed) {
            $this->onboardingService->assignEntityRole($user);
            $user->update(['onboarding_completed' => true]);
        }

        return view('onboarding.complete');
    }

    private function redirectIfAlreadyCompleted(User $user): ?RedirectResponse
    {
        return $user->onboarding_completed
            ? redirect()->route('second', ['dashboards', 'index'])
            : null;
    }
}
