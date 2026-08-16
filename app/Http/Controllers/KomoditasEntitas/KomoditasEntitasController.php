<?php

declare(strict_types=1);

namespace App\Http\Controllers\KomoditasEntitas;

use App\Enums\KomoditasEntitasStatus;
use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\KomoditasEntitas\KomoditasEntitasRequest;
use App\Http\Requests\KomoditasEntitas\KomoditasEntitasUpdateRequest;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\User;
use App\Notifications\AppNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KomoditasEntitasController extends Controller
{
    /**
     * Entity types allowed to submit komoditas entitas registrations.
     *
     * @var list<string>
     */
    private const ALLOWED_ENTITY_TYPES = ['perusahaan', 'penangkaran'];

    public function index(): View
    {
        $viewer = Auth::user();
        $canViewAll = $viewer->can('komoditas-entitas.view-all');
        $entity = $canViewAll ? $viewer->entity : $this->ownEntityOrAbort();

        $items = KomoditasEntitas::query()
            ->with(['komoditas', 'entitas'])
            ->when(! $canViewAll, fn ($query) => $query->where('entitas_id', $entity->id))
            ->latest()
            ->paginate(10);

        return view('komoditas-entitas.index', [
            'items' => $items,
            'komoditasOptions' => Komoditas::query()->orderBy('name')->get(),
            'canViewAll' => $canViewAll,
        ]);
    }

    public function store(KomoditasEntitasRequest $request): RedirectResponse
    {
        $entity = $this->ownEntityOrAbort();
        $komoditas = Komoditas::findOrFail($request->validated('komoditas_id'));

        $this->abortIfAlreadySubmitted($entity, $komoditas->id);

        $tanggalTerbit = CarbonImmutable::parse($request->validated('tanggal_terbit'));
        $path = $request->file('file')->store('komoditas-entitas/'.$entity->id, 'local');

        KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $entity->id,
            'file_path' => $path,
            'tanggal_terbit' => $tanggalTerbit,
            'tanggal_berakhir' => $tanggalTerbit->addYears(5),
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $this->notifyVerifiers($entity, $komoditas);

        return redirect()->route('komoditas-entitas.index')
            ->with('success', __('Komoditas entitas berhasil diajukan, menunggu verifikasi.'));
    }

    public function update(KomoditasEntitasUpdateRequest $request, KomoditasEntitas $komoditasEntitas): RedirectResponse
    {
        $entity = $this->authorizeOwner($komoditasEntitas);
        $this->abortUnlessRejected($komoditasEntitas);

        $komoditas = Komoditas::findOrFail($request->validated('komoditas_id'));

        $this->abortIfAlreadySubmitted($entity, $komoditas->id, except: $komoditasEntitas->id);

        $tanggalTerbit = CarbonImmutable::parse($request->validated('tanggal_terbit'));

        $data = [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => $tanggalTerbit,
            'tanggal_berakhir' => $tanggalTerbit->addYears(5),
            'status' => KomoditasEntitasStatus::Pending,
            'verified_by' => null,
            'verified_at' => null,
        ];

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($komoditasEntitas->file_path);
            $data['file_path'] = $request->file('file')->store('komoditas-entitas/'.$entity->id, 'local');
        }

        $komoditasEntitas->update($data);

        $this->notifyVerifiers($entity, $komoditas);

        return redirect()->route('komoditas-entitas.index')
            ->with('success', __('Pengajuan berhasil diperbarui, menunggu verifikasi ulang.'));
    }

    public function destroy(KomoditasEntitas $komoditasEntitas): RedirectResponse
    {
        $this->authorizeOwner($komoditasEntitas);
        $this->abortUnlessRejected($komoditasEntitas);

        Storage::disk('local')->delete($komoditasEntitas->file_path);
        $komoditasEntitas->delete();

        return redirect()->route('komoditas-entitas.index')
            ->with('success', __('Pengajuan berhasil dihapus.'));
    }

    private function notifyVerifiers(Entitas $entity, Komoditas $komoditas): void
    {
        $verifiers = User::permission('komoditas-entitas.verify')->get();

        if ($verifiers->isEmpty()) {
            return;
        }

        Notification::send($verifiers, new AppNotification(
            NotificationCategory::Permohonan,
            __('Pengajuan Komoditas Entitas Baru'),
            __(':entitas mengajukan komoditas :komoditas untuk diverifikasi.', [
                'entitas' => $entity->name,
                'komoditas' => $komoditas->name,
            ]),
            route('komoditas-entitas.verifikasi.index'),
        ));
    }

    public function download(KomoditasEntitas $komoditasEntitas): StreamedResponse
    {
        $this->authorizeDocumentAccess($komoditasEntitas);

        return Storage::disk('local')->download($komoditasEntitas->file_path);
    }

    public function preview(KomoditasEntitas $komoditasEntitas): StreamedResponse
    {
        $this->authorizeDocumentAccess($komoditasEntitas);

        return Storage::disk('local')->response($komoditasEntitas->file_path);
    }

    private function authorizeDocumentAccess(KomoditasEntitas $komoditasEntitas): void
    {
        $user = Auth::user();
        $isOwner = $user->entity_id !== null && $user->entity_id === $komoditasEntitas->entitas_id;

        abort_unless($isOwner || $user->can('komoditas-entitas.verify') || $user->can('komoditas-entitas.view-all'), 403);
    }

    /**
     * Confirms the authenticated user owns this submission (belongs to their entity) and
     * returns that entity - used by update()/destroy() where the entity is needed again.
     */
    private function authorizeOwner(KomoditasEntitas $komoditasEntitas): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless($entity !== null && $entity->id === $komoditasEntitas->entitas_id, 403);

        return $entity;
    }

    private function abortUnlessRejected(KomoditasEntitas $komoditasEntitas): void
    {
        abort_unless($komoditasEntitas->status === KomoditasEntitasStatus::Rejected, 403);
    }

    /**
     * An entity may only have one non-rejected (pending/verified) submission per commodity at
     * a time - a rejected submission must be edited or deleted before resubmitting the same
     * commodity.
     */
    private function abortIfAlreadySubmitted(Entitas $entity, int $komoditasId, ?int $except = null): void
    {
        $exists = KomoditasEntitas::query()
            ->where('entitas_id', $entity->id)
            ->where('komoditas_id', $komoditasId)
            ->where('status', '!=', KomoditasEntitasStatus::Rejected)
            ->when($except !== null, fn ($query) => $query->where('id', '!=', $except))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'komoditas_id' => __('Komoditas ini sudah pernah diajukan dan masih menunggu/terverifikasi.'),
            ]);
        }
    }

    private function ownEntityOrAbort(): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless(
            $entity !== null && in_array($entity->entity_type, self::ALLOWED_ENTITY_TYPES, true),
            403
        );

        return $entity;
    }
}
