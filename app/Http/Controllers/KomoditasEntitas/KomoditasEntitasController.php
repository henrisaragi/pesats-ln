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
use App\Services\DokumenVerifikasi\KomoditasEntitasAutoVerifikasiService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class KomoditasEntitasController extends Controller
{
    /**
     * Entity types allowed to submit komoditas entitas registrations.
     *
     * @var list<string>
     */
    private const ALLOWED_ENTITY_TYPES = ['perusahaan', 'penangkaran'];

    public function __construct(private readonly KomoditasEntitasAutoVerifikasiService $autoVerifikasiService) {}

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
        $file = $request->file('file');
        $path = $this->buildObjectKey($entity, $request->validated('nomor_sertifikat'), $file->extension() ?: 'pdf');

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

        $komoditasEntitas = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $entity->id,
            'nomor_sertifikat' => $request->validated('nomor_sertifikat'),
            'file_path' => $path,
            'tanggal_terbit' => $tanggalTerbit,
            'tanggal_berakhir' => $tanggalTerbit->addYears(5),
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        return redirect()->route('komoditas-entitas.index')
            ->with('success', $this->jalankanVerifikasiOtomatis($komoditasEntitas, $entity, $komoditas)
                ? __('Komoditas entitas berhasil diajukan dan lolos verifikasi otomatis.')
                : __('Komoditas entitas berhasil diajukan, menunggu verifikasi.'));
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
            'nomor_sertifikat' => $request->validated('nomor_sertifikat'),
            'tanggal_terbit' => $tanggalTerbit,
            'tanggal_berakhir' => $tanggalTerbit->addYears(5),
            'status' => KomoditasEntitasStatus::Pending,
            'verified_by' => null,
            'verified_at' => null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $newPath = $this->buildObjectKey($entity, $data['nomor_sertifikat'], $file->extension() ?: 'pdf');

            Storage::disk('s3')->put($newPath, file_get_contents($file->getRealPath()));
            Storage::disk('s3')->delete($komoditasEntitas->file_path);

            $data['file_path'] = $newPath;
        }

        $komoditasEntitas->update($data);

        return redirect()->route('komoditas-entitas.index')
            ->with('success', $this->jalankanVerifikasiOtomatis($komoditasEntitas, $entity, $komoditas)
                ? __('Pengajuan berhasil diperbarui dan lolos verifikasi otomatis.')
                : __('Pengajuan berhasil diperbarui, menunggu verifikasi ulang.'));
    }

    public function destroy(KomoditasEntitas $komoditasEntitas): RedirectResponse
    {
        $this->authorizeOwner($komoditasEntitas);
        $this->abortUnlessRejected($komoditasEntitas);

        Storage::disk('s3')->delete($komoditasEntitas->file_path);
        $komoditasEntitas->delete();

        return redirect()->route('komoditas-entitas.index')
            ->with('success', __('Pengajuan berhasil dihapus.'));
    }

    /**
     * Jalankan pemeriksaan otomatis dokumen (lihat KomoditasEntitasAutoVerifikasiService /
     * claude/otomatis-verifikasi.md). Kalau lolos semua, statusnya sudah Verified saat method ini
     * selesai sehingga verifier TIDAK perlu diberi tahu. Kalau gagal (termasuk kalau proses
     * pemeriksaannya sendiri error, mis. dokumen tidak bisa dibaca), dibiarkan Pending seperti
     * semula dan verifier diberi tahu untuk verifikasi manual seperti alur sebelumnya.
     * Mengembalikan true kalau lolos otomatis.
     */
    private function jalankanVerifikasiOtomatis(KomoditasEntitas $komoditasEntitas, Entitas $entity, Komoditas $komoditas): bool
    {
        try {
            $this->autoVerifikasiService->verifikasi($komoditasEntitas);
        } catch (Throwable $e) {
            Log::error('Verifikasi otomatis Komoditas Entitas gagal.', [
                'komoditas_entitas_id' => $komoditasEntitas->id,
                'exception' => $e->getMessage(),
            ]);
        }

        if ($komoditasEntitas->fresh()->status === KomoditasEntitasStatus::Verified) {
            $this->notifySubmitterAutoVerified($komoditasEntitas, $komoditas);

            return true;
        }

        $this->notifyVerifiers($entity, $komoditas);

        return false;
    }

    private function notifySubmitterAutoVerified(KomoditasEntitas $komoditasEntitas, Komoditas $komoditas): void
    {
        $komoditasEntitas->loadMissing('entitas.users');
        $recipients = $komoditasEntitas->entitas?->users ?? collect();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AppNotification(
            NotificationCategory::Permohonan,
            __('Komoditas Entitas Terverifikasi Otomatis'),
            __('Pengajuan komoditas :komoditas lolos verifikasi otomatis.', ['komoditas' => $komoditas->name]),
            route('komoditas-entitas.index'),
        ));
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

    public function download(KomoditasEntitas $komoditasEntitas): RedirectResponse
    {
        $this->authorizeDocumentAccess($komoditasEntitas);

        $url = Storage::disk('s3')->temporaryUrl($komoditasEntitas->file_path, now()->addMinutes(10), [
            'ResponseContentDisposition' => 'attachment; filename="'.basename($komoditasEntitas->file_path).'"',
        ]);

        return redirect($url);
    }

    public function preview(KomoditasEntitas $komoditasEntitas): RedirectResponse
    {
        $this->authorizeDocumentAccess($komoditasEntitas);

        $url = Storage::disk('s3')->temporaryUrl($komoditasEntitas->file_path, now()->addMinutes(10));

        return redirect($url);
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
     * Sebuah entitas cuma boleh punya SATU pengajuan yang masih "aktif" (Pending/Verified) per
     * komoditas dalam satu waktu. Rejected maupun Expired TIDAK menghalangi pengajuan baru -
     * pengajuan lama yang sudah Expired sengaja dibiarkan apa adanya (bukan diedit/ditimpa) supaya
     * tetap ada riwayat kapan komoditas itu pernah kedaluwarsa, baris baru dibuat terpisah lewat
     * store().
     */
    private function abortIfAlreadySubmitted(Entitas $entity, int $komoditasId, ?int $except = null): void
    {
        $exists = KomoditasEntitas::query()
            ->where('entitas_id', $entity->id)
            ->where('komoditas_id', $komoditasId)
            ->whereIn('status', [KomoditasEntitasStatus::Pending, KomoditasEntitasStatus::Verified])
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

    /**
     * Object key di bucket S3: folder {id}-{nama_entitas}/{tahun-bulan} supaya bisa ditelusuri
     * manual dari MinIO Console, lalu file bernama nomor sertifikat (slug) + timestamp supaya
     * tidak collision antar upload - pola sama seperti DocumentController::buildObjectKey().
     */
    private function buildObjectKey(Entitas $entitas, string $nomorSertifikat, string $extension): string
    {
        $slugNomor = Str::slug($nomorSertifikat, '-') ?: 'sertifikat';
        $filename = sprintf('%s_%s.%s', $slugNomor, now()->format('YmdHis'), $extension);
        $entityFolder = $entitas->id.'-'.(Str::slug($entitas->name, '_') ?: 'entitas');
        $monthFolder = now()->format('Y-m');

        return "komoditas-entitas/{$entityFolder}/{$monthFolder}/{$filename}";
    }
}
