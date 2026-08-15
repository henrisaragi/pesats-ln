<?php

declare(strict_types=1);

namespace App\Http\Controllers\KomoditasEntitas;

use App\Enums\KomoditasEntitasStatus;
use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Models\KomoditasEntitas;
use App\Notifications\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class KomoditasEntitasVerifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->filled('status')
            ? KomoditasEntitasStatus::tryFrom($request->string('status')->toString())
            : KomoditasEntitasStatus::Pending;

        $items = KomoditasEntitas::query()
            ->with(['komoditas', 'entitas'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('komoditas-entitas.verifikasi.index', [
            'items' => $items,
            'activeStatus' => $status,
        ]);
    }

    public function verify(KomoditasEntitas $komoditasEntitas): RedirectResponse
    {
        $komoditasEntitas->update([
            'status' => KomoditasEntitasStatus::Verified,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        $this->notifySubmitter(
            $komoditasEntitas,
            __('Pengajuan Komoditas Entitas Terverifikasi'),
            __('Pengajuan komoditas :komoditas telah diverifikasi.', ['komoditas' => $komoditasEntitas->komoditas?->name])
        );

        return redirect()->route('komoditas-entitas.verifikasi.index')
            ->with('success', __('Komoditas entitas berhasil diverifikasi.'));
    }

    public function reject(KomoditasEntitas $komoditasEntitas): RedirectResponse
    {
        $komoditasEntitas->update([
            'status' => KomoditasEntitasStatus::Rejected,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        $this->notifySubmitter(
            $komoditasEntitas,
            __('Pengajuan Komoditas Entitas Ditolak'),
            __('Pengajuan komoditas :komoditas ditolak.', ['komoditas' => $komoditasEntitas->komoditas?->name])
        );

        return redirect()->route('komoditas-entitas.verifikasi.index')
            ->with('success', __('Komoditas entitas berhasil ditolak.'));
    }

    private function notifySubmitter(KomoditasEntitas $komoditasEntitas, string $title, string $message): void
    {
        $komoditasEntitas->loadMissing(['entitas.users', 'komoditas']);

        $recipients = $komoditasEntitas->entitas?->users ?? collect();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AppNotification(
            NotificationCategory::Permohonan,
            $title,
            $message,
            route('komoditas-entitas.index'),
        ));
    }
}
