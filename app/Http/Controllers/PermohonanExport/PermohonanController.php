<?php

declare(strict_types=1);

namespace App\Http\Controllers\PermohonanExport;

use App\Enums\DocumentType;
use App\Enums\JenisPermohonan;
use App\Enums\NotificationCategory;
use App\Enums\PermohonanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermohonanExport\PermohonanRequest;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Customer\Customer;
use App\Models\Document;
use App\Models\Entitas\Entitas;
use App\Models\Permohonan;
use App\Models\PurposeOfTransaction;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\PermohonanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PermohonanController extends Controller
{
    public function __construct(private readonly PermohonanService $permohonanService) {}

    public function index(): View
    {
        $entity = $this->ownEntityOrAbort();

        $permohonan = Permohonan::query()
            ->withCount('satsLn')
            ->where('entity_id', $entity->id)
            ->latest()
            ->paginate(10);

        return view('permohonan-export.index', [
            'permohonan' => $permohonan,
            'documentOptions' => Document::query()
                ->where('entity_id', $entity->id)
                ->where('type_document', DocumentType::Permohonan)
                ->whereNull('permohonan_id')
                ->orderByDesc('id')
                ->get(),
            'jenisPermohonanOptions' => JenisPermohonan::cases(),
            'nomorPermohonanPreview' => $this->permohonanService->nextNomorPermohonanPreview(),
        ]);
    }

    public function store(PermohonanRequest $request): RedirectResponse
    {
        $entity = $this->ownEntityOrAbort();

        $permohonan = DB::transaction(function () use ($entity, $request): Permohonan {
            $permohonan = Permohonan::create([
                'entity_id' => $entity->id,
                'nomor_permohonan' => $this->permohonanService->generateNomorPermohonan(),
                'jenis_permohonan' => $request->validated('jenis_permohonan'),
                'status' => PermohonanStatus::Draft,
            ]);

            Document::whereKey($request->validated('document_id'))->update(['permohonan_id' => $permohonan->id]);

            return $permohonan;
        });

        return redirect()->route('permohonan-export.show', $permohonan)
            ->with('success', __('Permohonan berhasil dibuat.'));
    }

    public function edit(Permohonan $permohonan): View
    {
        $entity = $this->authorizeOwner($permohonan);

        return view('permohonan-export.edit', [
            'permohonan' => $permohonan->load(['satsLn.items.species', 'satsLn.items.kuotaPerusahaan.kuotaNasional.source', 'satsLn.purposeOfTransaction', 'satsLn.customerCompany']),
            'entity' => $entity,
            'purposeOfTransactionOptions' => PurposeOfTransaction::query()->orderBy('name')->get(),
            'customerCompanyOptions' => Customer::query()->where('entity_id', $entity->id)->orderBy('name')->get(),
            'sourceStatusOptions' => $this->permohonanService->sourceStatusOptionsForEntitas($entity),
        ]);
    }

    public function show(Permohonan $permohonan): View
    {
        $this->authorizeOwner($permohonan);

        return view('permohonan-export.show', [
            'permohonan' => $permohonan->load(['entitas', 'document', 'satsLn' => fn ($query) => $query->oldest()]),
        ]);
    }

    public function submit(Permohonan $permohonan): RedirectResponse
    {
        $this->authorizeOwner($permohonan);
        abort_unless($permohonan->status->isEditable(), 403);

        if ($permohonan->satsLn()->count() === 0) {
            return redirect()->route('permohonan-export.edit', $permohonan)
                ->with('error', __('Minimal 1 SATS-LN harus diisi sebelum permohonan diajukan.'));
        }

        $permohonan->update([
            'status' => PermohonanStatus::AntrianVerifikasi,
            'submitted_at' => now(),
            'verified_by' => null,
            'verified_at' => null,
            'catatan_verifikasi' => null,
        ]);

        $this->notifyVerifiers($permohonan);

        return redirect()->route('permohonan-export.index')
            ->with('success', __('Permohonan berhasil diajukan, menunggu verifikasi.'));
    }

    public function destroy(Permohonan $permohonan): RedirectResponse
    {
        $this->authorizeOwner($permohonan);
        abort_unless($permohonan->status->isEditable(), 403);

        $permohonan->delete();

        return redirect()->route('permohonan-export.index')->with('success', __('Permohonan berhasil dihapus.'));
    }

    private function notifyVerifiers(Permohonan $permohonan): void
    {
        $verifiers = User::permission('permohonan-export.verify')->get();

        if ($verifiers->isEmpty()) {
            return;
        }

        $message = __(':entitas mengajukan permohonan :nomor untuk diverifikasi.', [
            'entitas' => $permohonan->entitas?->name,
            'nomor' => $permohonan->nomor_permohonan,
        ]);

        Notification::send($verifiers, new AppNotification(
            NotificationCategory::Permohonan,
            __('Permohonan SATS-LN Ekspor Baru'),
            $message,
            route('permohonan-export.verifikasi.show', $permohonan),
        ));

        // Contoh pemakaian KapsoWhatsAppService (lewat SendWhatsAppNotificationJob, yang
        // menangani cek on/off App\Models\WhatsAppSetting) - cukup dispatch, tidak perlu tahu
        // soal toggle atau konfigurasi Kapso di sini.
        foreach ($verifiers->whereNotNull('phone_number') as $verifier) {
            SendWhatsAppNotificationJob::dispatch($verifier->phone_number, $message);
        }
    }

    private function authorizeOwner(Permohonan $permohonan): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless($entity !== null && $entity->id === $permohonan->entity_id, 403);

        return $entity;
    }

    private function ownEntityOrAbort(): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless($entity !== null && $entity->entity_type === 'perusahaan', 403);

        return $entity;
    }
}
