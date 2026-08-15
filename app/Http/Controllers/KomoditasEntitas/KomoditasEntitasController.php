<?php

declare(strict_types=1);

namespace App\Http\Controllers\KomoditasEntitas;

use App\Enums\KomoditasEntitasStatus;
use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\KomoditasEntitas\KomoditasEntitasRequest;
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
        $entity = $this->ownEntityOrAbort();

        $items = KomoditasEntitas::query()
            ->with('komoditas')
            ->where('entitas_id', $entity->id)
            ->latest()
            ->paginate(10);

        return view('komoditas-entitas.index', [
            'items' => $items,
            'komoditasOptions' => Komoditas::query()->orderBy('name')->get(),
        ]);
    }

    public function store(KomoditasEntitasRequest $request): RedirectResponse
    {
        $entity = $this->ownEntityOrAbort();
        $komoditas = Komoditas::findOrFail($request->validated('komoditas_id'));

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

        abort_unless($isOwner || $user->can('komoditas-entitas.verify'), 403);
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
