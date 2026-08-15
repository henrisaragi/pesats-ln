<?php

declare(strict_types=1);

namespace App\Http\Controllers\Entitas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entitas\EntityProfileRequest;
use App\Models\Entitas\Entitas;
use App\Services\Entitas\EntityProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EntityProfileController extends Controller
{
    public function __construct(private readonly EntityProfileService $profileService) {}

    public function show(Entitas $entity): View
    {
        return view('master.entitas.profile', [
            'entity' => $entity,
            'fields' => $this->profileService->fields($entity->entity_type),
            'profile' => $this->profileService->find($entity),
        ]);
    }

    public function store(EntityProfileRequest $request, Entitas $entity): RedirectResponse
    {
        $this->profileService->save($entity, $request->validated());

        return redirect()->route('master.entitas.profile.show', $entity)
            ->with('success', __('Profil entitas berhasil dibuat.'));
    }

    public function update(EntityProfileRequest $request, Entitas $entity): RedirectResponse
    {
        $this->profileService->save($entity, $request->validated());

        return redirect()->route('master.entitas.profile.show', $entity)
            ->with('success', __('Profil entitas berhasil diperbarui.'));
    }

    public function destroy(Entitas $entity): RedirectResponse
    {
        $this->profileService->delete($entity);

        return redirect()->route('master.entitas.profile.show', $entity)
            ->with('success', __('Profil entitas berhasil dihapus.'));
    }
}
