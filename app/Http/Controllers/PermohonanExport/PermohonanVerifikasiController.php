<?php

declare(strict_types=1);

namespace App\Http\Controllers\PermohonanExport;

use App\Enums\MasaBerlakuSatsLn;
use App\Enums\PermohonanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermohonanExport\DisposisiRequest;
use App\Http\Requests\PermohonanExport\ReopenSatsLnRequest;
use App\Http\Requests\PermohonanExport\VerifySatsLnRequest;
use App\Models\Document;
use App\Models\Permohonan;
use App\Models\SatsLn;
use App\Models\User;
use App\Services\PermohonanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PermohonanVerifikasiController extends Controller
{
    public function __construct(private readonly PermohonanService $permohonanService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $allowedStatuses = array_values(array_filter(
            PermohonanStatus::cases(),
            fn (PermohonanStatus $status): bool => $user->can($status->verifikasiFilterPermission()),
        ));
        $canFilterAll = $user->can(PermohonanStatus::VERIFIKASI_FILTER_ALL_PERMISSION);

        if ($request->filled('status')) {
            $requested = $request->string('status')->toString();
            $status = $requested === 'all' ? null : PermohonanStatus::tryFrom($requested);

            abort_unless($status === null ? $requested === 'all' && $canFilterAll : in_array($status, $allowedStatuses, true), 403);
        } else {
            $status = in_array(PermohonanStatus::AntrianVerifikasi, $allowedStatuses, true)
                ? PermohonanStatus::AntrianVerifikasi
                : ($allowedStatuses[0] ?? null);
        }

        $hasFilterAccess = $status !== null || $canFilterAll;

        $permohonan = Permohonan::query()
            ->with(['entitas', 'verifiedBy'])
            ->withCount('satsLn')
            ->where('year', $this->activeYear($request))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when(! $hasFilterAccess, fn ($query) => $query->whereRaw('1 = 0'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('permohonan-export.verifikasi.index', [
            'permohonan' => $permohonan,
            'activeStatus' => $status,
            'allowedStatuses' => $allowedStatuses,
            'canFilterAll' => $canFilterAll,
            'hasFilterAccess' => $hasFilterAccess,
        ]);
    }

    /**
     * Daftar Permohonan yang minimal satu SATS-LN-nya diverifikasi oleh user yang sedang login.
     */
    public function mine(Request $request): View
    {
        $permohonan = Permohonan::query()
            ->with(['entitas', 'verifiedBy'])
            ->withCount('satsLn')
            ->where('year', $this->activeYear($request))
            ->whereHas('satsLn', fn ($query) => $query->where('verified_id', $request->user()->id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('permohonan-export.verifikasi.index', [
            'permohonan' => $permohonan,
            'isMine' => true,
        ]);
    }

    public function show(Permohonan $permohonan, ?SatsLn $satsLn = null): View
    {
        if ($satsLn !== null) {
            abort_unless($satsLn->permohonan_id === $permohonan->id, 404);
        }

        $permohonan->load([
            'entitas',
            'satsLn' => fn ($query) => $query->oldest(),
            'satsLn.customerCompany.country',
            'satsLn.purposeOfTransaction',
            'satsLn.portOfExportation',
            'satsLn.portOfDestination',
            'satsLn.items.species',
            'satsLn.items.kuotaNasional.komoditas',
            'satsLn.items.kuotaNasional.source',
            'satsLn.items.kuotaNasional.tradeCode',
            'satsLn.items.kuotaNasional.species',
            'satsLn.items.kuotaPerusahaan.kuotaNasional.tradeCode',
            'satsLn.items.kuotaPerusahaan.kuotaNasional.source',
            'satsLn.documents',
            'activities' => fn ($query) => $query->latest('id'),
            'activities.user',
        ]);

        $selectedSatsLn = $satsLn ?? $permohonan->satsLn->last();

        return view('permohonan-export.verifikasi.show', [
            'permohonan' => $permohonan,
            'selectedSatsLn' => $selectedSatsLn,
            'realisasiNasionalRunningTotalByItemId' => $this->permohonanService->realisasiKuotaNasionalRunningTotalForItems(
                $selectedSatsLn?->items ?? collect()
            ),
            'verifikatorOptions' => User::whereHas('roles', fn ($query) => $query->where('name', 'verifikator'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Pratinjau dokumen lampiran SATS-LN untuk staf verifikasi - sengaja tidak lewat
     * documents.preview (yang mensyaratkan documents.view-all untuk lintas entitas), aksesnya
     * dibatasi ke dokumen yang memang terlampir di SATS-LN Permohonan ini.
     */
    public function previewDocument(Permohonan $permohonan, SatsLn $satsLn, Document $document): RedirectResponse
    {
        $this->authorizeSatsLnDocument($permohonan, $satsLn, $document);

        return redirect(Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(10)));
    }

    public function downloadDocument(Permohonan $permohonan, SatsLn $satsLn, Document $document): RedirectResponse
    {
        $this->authorizeSatsLnDocument($permohonan, $satsLn, $document);

        return redirect(Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(10), [
            'ResponseContentDisposition' => 'attachment; filename="'.basename($document->path).'"',
        ]));
    }

    public function disposisi(DisposisiRequest $request, Permohonan $permohonan): RedirectResponse
    {
        abort_unless($permohonan->status === PermohonanStatus::Submitted, 403);

        $this->permohonanService->changeStatus($permohonan, PermohonanStatus::AntrianVerifikasi, [
            'verified_by' => $request->validated('verifikator_id'),
        ]);

        return redirect()->route('permohonan-export.verifikasi.index')->with('success', __('Permohonan berhasil didisposisikan.'));
    }

    public function disposisiCetak(Request $request, Permohonan $permohonan): RedirectResponse
    {
        $this->permohonanService->disposisiCetak($permohonan, $request->user());

        return redirect()->route('permohonan-export.verifikasi.show', $permohonan)->with('success', __('Permohonan berhasil dikirim ke Divisi Cetak.'));
    }

    public function verify(Permohonan $permohonan): RedirectResponse
    {
        abort_unless($permohonan->status === PermohonanStatus::AntrianVerifikasi, 403);

        $this->permohonanService->changeStatus($permohonan, PermohonanStatus::DalamProsesVerifikasi, [
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return redirect()->route('permohonan-export.verifikasi.index')->with('success', __('Permohonan berhasil diverifikasi.'));
    }

    public function verifySatsLn(VerifySatsLnRequest $request, Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        if (! $request->isAgreed()) {
            $this->permohonanService->disagreeSatsLn($permohonan, $satsLn, $request->validated('pesan'), $request->user());

            return redirect()->route('permohonan-export.verifikasi.sats-ln.show', [$permohonan, $satsLn])
                ->with('success', __('Pesan untuk SATS-LN berhasil disimpan.'));
        }

        $satsLn->loadMissing('items.species');

        $this->permohonanService->verifySatsLn($permohonan, $satsLn, [
            'satsln_number_issued' => $request->satslnNumberIssued(),
            'year' => $request->integer('year'),
            'security_stamp' => $satsLn->requiresSecurityStamp() ? $request->validated('security_stamp') : null,
            'masa_berlaku' => MasaBerlakuSatsLn::from($request->validated('masa_berlaku')),
            'publish_date' => $request->validated('publish_date'),
            'special_conditions' => $request->validated('special_conditions'),
        ], $request->user());

        return redirect()->route('permohonan-export.verifikasi.sats-ln.show', [$permohonan, $satsLn])
            ->with('success', __('SATS-LN berhasil diverifikasi.'));
    }

    public function reopenSatsLn(ReopenSatsLnRequest $request, Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        $this->permohonanService->reopenSatsLn($permohonan, $satsLn, $request->validated('pesan'), $request->user());

        return redirect()->route('permohonan-export.verifikasi.sats-ln.show', [$permohonan, $satsLn])
            ->with('success', __('Verifikasi SATS-LN dibuka kembali. Silakan ubah data lewat tombol Verifikasi.'));
    }

    /**
     * Cek nomor SATS-LN secara live dari modal Verifikasi (validasi final tetap di VerifySatsLnRequest).
     */
    public function checkSatsLnNumber(Request $request): JsonResponse
    {
        $number = $request->string('number')->toString();
        $year = $request->integer('year');

        if (! preg_match('/^\d{1,5}$/', $number) || $year < 2000 || $year > 2099) {
            return response()->json(['available' => true]);
        }

        // exclude = public_id SATS-LN yang sedang diverifikasi ulang, supaya nomornya sendiri tidak dianggap bentrok.
        $exceptSatsLnId = $request->filled('exclude')
            ? SatsLn::query()->where('public_id', $request->string('exclude')->toString())->value('id')
            : null;

        $exists = SatsLn::issuedNumberExists(SatsLn::composeIssuedNumber($year, $number), $exceptSatsLnId);

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? __('Nomor telah digunakan.') : null,
        ]);
    }

    public function reject(Request $request, Permohonan $permohonan): RedirectResponse
    {
        abort_unless(in_array($permohonan->status, [PermohonanStatus::AntrianVerifikasi, PermohonanStatus::DalamProsesVerifikasi], true), 403);

        $this->permohonanService->changeStatus($permohonan, PermohonanStatus::Ditolak, [
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'catatan_verifikasi' => $request->string('catatan_verifikasi')->toString() ?: null,
        ]);

        return redirect()->route('permohonan-export.verifikasi.index')->with('success', __('Permohonan berhasil ditolak.'));
    }

    public function markPrinted(Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);
        abort_unless($permohonan->status === PermohonanStatus::DalamProsesVerifikasi || $permohonan->status === PermohonanStatus::AntrianCetak || $permohonan->status === PermohonanStatus::DicetakSebagian || $permohonan->status === PermohonanStatus::DitandatanganiSebagian, 403);

        $satsLn->update(['printed_at' => now(), 'printed_user_id' => Auth::id()]);
        $this->permohonanService->recomputeStatus($permohonan);

        return redirect()->route('permohonan-export.verifikasi.show', $permohonan)->with('success', __('SATS-LN ditandai sudah dicetak.'));
    }

    public function markSigned(Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);
        abort_unless($permohonan->status === PermohonanStatus::DalamProsesVerifikasi || $permohonan->status === PermohonanStatus::AntrianCetak || $permohonan->status === PermohonanStatus::DicetakSebagian || $permohonan->status === PermohonanStatus::DitandatanganiSebagian, 403);

        $satsLn->update(['signed_at' => now(), 'issued_by_id' => Auth::id()]);
        $this->permohonanService->recomputeStatus($permohonan);

        return redirect()->route('permohonan-export.verifikasi.show', $permohonan)->with('success', __('SATS-LN ditandai sudah ditandatangani.'));
    }

    private function authorizeSatsLnDocument(Permohonan $permohonan, SatsLn $satsLn, Document $document): void
    {
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);
        abort_unless($satsLn->documents()->whereKey($document->id)->exists(), 404);
    }
}
