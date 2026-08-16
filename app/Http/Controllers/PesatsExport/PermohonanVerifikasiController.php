<?php

declare(strict_types=1);

namespace App\Http\Controllers\PesatsExport;

use App\Enums\NotificationCategory;
use App\Enums\PermohonanStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Permohonan;
use App\Models\SatsLn;
use App\Notifications\AppNotification;
use App\Services\PermohonanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class PermohonanVerifikasiController extends Controller
{
    public function __construct(private readonly PermohonanService $permohonanService) {}

    public function index(Request $request): View
    {
        $status = $request->filled('status')
            ? PermohonanStatus::tryFrom($request->string('status')->toString())
            : PermohonanStatus::AntrianVerifikasi;

        $permohonan = Permohonan::query()
            ->with('entitas')
            ->withCount('satsLn')
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pesats-export.verifikasi.index', [
            'permohonan' => $permohonan,
            'activeStatus' => $status,
        ]);
    }

    public function show(Permohonan $permohonan): View
    {
        return view('pesats-export.verifikasi.show', [
            'permohonan' => $permohonan->load([
                'entitas',
                'satsLn.items.species',
                'satsLn.items.kuotaPerusahaan.kuotaNasional.source',
                'satsLn.purposeOfTransaction',
                'satsLn.customerCompany',
                'satsLn.printedBy',
                'satsLn.issuedBy',
            ]),
        ]);
    }

    public function verify(Permohonan $permohonan): RedirectResponse
    {
        abort_unless($permohonan->status === PermohonanStatus::AntrianVerifikasi, 403);

        $permohonan->update([
            'status' => PermohonanStatus::DalamProsesVerifikasi,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        $this->notifySubmitter($permohonan, __('Permohonan Diverifikasi'), __('Permohonan :nomor telah diverifikasi dan mulai diproses.', ['nomor' => $permohonan->nomor_permohonan]));

        return redirect()->route('pesats-export.verifikasi.index')->with('success', __('Permohonan berhasil diverifikasi.'));
    }

    public function reject(Request $request, Permohonan $permohonan): RedirectResponse
    {
        abort_unless(in_array($permohonan->status, [PermohonanStatus::AntrianVerifikasi, PermohonanStatus::DalamProsesVerifikasi], true), 403);

        $permohonan->update([
            'status' => PermohonanStatus::Ditolak,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'catatan_verifikasi' => $request->string('catatan_verifikasi')->toString() ?: null,
        ]);

        $this->notifySubmitter($permohonan, __('Permohonan Ditolak'), __('Permohonan :nomor ditolak.', ['nomor' => $permohonan->nomor_permohonan]));

        return redirect()->route('pesats-export.verifikasi.index')->with('success', __('Permohonan berhasil ditolak.'));
    }

    public function markPrinted(Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);
        abort_unless($permohonan->status === PermohonanStatus::DalamProsesVerifikasi || $permohonan->status === PermohonanStatus::DicetakSebagian || $permohonan->status === PermohonanStatus::DitandatanganiSebagian, 403);

        $satsLn->update(['printed_at' => now(), 'printed_user_id' => Auth::id()]);
        $this->permohonanService->recomputeStatus($permohonan);

        return redirect()->route('pesats-export.verifikasi.show', $permohonan)->with('success', __('SATS-LN ditandai sudah dicetak.'));
    }

    public function markSigned(Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);
        abort_unless($permohonan->status === PermohonanStatus::DalamProsesVerifikasi || $permohonan->status === PermohonanStatus::DicetakSebagian || $permohonan->status === PermohonanStatus::DitandatanganiSebagian, 403);

        $satsLn->update(['signed_at' => now(), 'issued_by_id' => Auth::id()]);
        $this->permohonanService->recomputeStatus($permohonan);

        return redirect()->route('pesats-export.verifikasi.show', $permohonan)->with('success', __('SATS-LN ditandai sudah ditandatangani.'));
    }

    private function notifySubmitter(Permohonan $permohonan, string $title, string $message): void
    {
        $permohonan->loadMissing('entitas.users');

        $recipients = $permohonan->entitas?->users ?? collect();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AppNotification(
            NotificationCategory::Permohonan,
            $title,
            $message,
            route('pesats-export.show', $permohonan),
        ));

        // Contoh pemakaian lain KapsoWhatsAppService di titik notifikasi yang berbeda
        // (lihat juga PermohonanController::notifyVerifiers()).
        foreach ($recipients->whereNotNull('phone_number') as $recipient) {
            SendWhatsAppNotificationJob::dispatch($recipient->phone_number, $message);
        }
    }
}
