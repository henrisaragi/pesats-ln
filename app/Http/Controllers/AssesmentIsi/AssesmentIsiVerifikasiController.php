<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssesmentIsi;

use App\Enums\AssesmentIsiStatus;
use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssesmentIsi\AssesmentIsiVerifikasiJawabanRequest;
use App\Http\Requests\AssesmentIsi\AssesmentIsiVerifikasiRequest;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Assesment\AssesmentPertanyaan;
use App\Notifications\AppNotification;
use App\Services\Assesment\AssesmentSertifikatService;
use App\Services\Assesment\AssesmentSkorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssesmentIsiVerifikasiController extends Controller
{
    public function __construct(
        private readonly AssesmentSkorService $skorService,
        private readonly AssesmentSertifikatService $sertifikatService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->filled('status')
            ? AssesmentIsiStatus::tryFrom($request->string('status')->toString())
            : AssesmentIsiStatus::Submitted;

        $items = AssesmentIsi::query()
            ->with(['assesment', 'entitas', 'komoditas'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('assesment-isi.verifikasi.index', [
            'items' => $items,
            'activeStatus' => $status,
        ]);
    }

    public function show(AssesmentIsi $isi): View
    {
        $isi->load(['assesment', 'entitas', 'komoditas', 'bukti.buktiDukung']);

        $pertanyaan = $isi->assesment->pertanyaan()
            ->whereHas('komoditas', fn ($query) => $query->where('komoditas.id', $isi->komoditas_id))
            ->with(['aspek', 'jawaban.komoditas', 'buktiDukung'])
            ->orderBy('urutan')
            ->get();

        $isiJawaban = $isi->jawaban()->get()->keyBy('pertanyaan_id');

        return view('assesment-isi.verifikasi.show', [
            'isi' => $isi,
            'pertanyaan' => $pertanyaan,
            'isiJawaban' => $isiJawaban,
        ]);
    }

    /**
     * Simpan koreksi verifikator untuk satu pertanyaan - dipanggil setiap tombol "Simpan & Lanjut"
     * pada wizard verifikasi, jadi progres tidak hilang kalau halaman ditinggal di tengah jalan.
     */
    public function saveJawaban(AssesmentIsiVerifikasiJawabanRequest $request, AssesmentIsi $isi, AssesmentPertanyaan $pertanyaan): JsonResponse
    {
        abort_unless($isi->status === AssesmentIsiStatus::Submitted, 403, __('Assesment ini sudah tidak dapat diubah.'));

        $isiJawaban = $isi->jawaban()->where('pertanyaan_id', $pertanyaan->id)->firstOrFail();

        $isiJawaban->update(['jawaban_verifikator_id' => (int) $request->validated('jawaban_id')]);

        return response()->json(['message' => __('Jawaban tersimpan.')]);
    }

    public function complete(AssesmentIsiVerifikasiRequest $request, AssesmentIsi $isi): RedirectResponse
    {
        abort_unless($isi->status === AssesmentIsiStatus::Submitted, 403);

        $pertanyaanIds = $isi->assesment->pertanyaan()
            ->whereHas('komoditas', fn ($query) => $query->where('komoditas.id', $isi->komoditas_id))
            ->pluck('id');

        $jawaban = (array) $request->validated('jawaban');

        if (count(array_intersect_key(array_flip($pertanyaanIds->all()), $jawaban)) < $pertanyaanIds->count()) {
            throw ValidationException::withMessages([
                'jawaban' => __('Semua pertanyaan harus diverifikasi.'),
            ]);
        }

        DB::transaction(function () use ($isi, $jawaban, $pertanyaanIds): void {
            foreach ($pertanyaanIds as $pertanyaanId) {
                $isi->jawaban()->where('pertanyaan_id', $pertanyaanId)->update([
                    'jawaban_verifikator_id' => $jawaban[$pertanyaanId],
                ]);
            }
        });

        $skorTotal = $this->skorService->hitung($isi->fresh());

        $isi->update([
            'status' => AssesmentIsiStatus::Verified,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'skor_total' => $skorTotal,
        ]);

        $sertifikatPath = $this->sertifikatService->generate($isi->fresh());
        $isi->update(['sertifikat_path' => $sertifikatPath]);

        $this->notifySubmitter($isi);

        return redirect()->route('assesment-isi.verifikasi.index')->with('success', __('Assesment berhasil diverifikasi dan sertifikat diterbitkan.'));
    }

    private function notifySubmitter(AssesmentIsi $isi): void
    {
        $isi->loadMissing(['entitas.users', 'assesment']);

        $recipients = $isi->entitas?->users ?? collect();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AppNotification(
            NotificationCategory::Permohonan,
            __('Assesment Terverifikasi'),
            __('Assesment :assesment telah diverifikasi, sertifikat sudah dapat diunduh.', ['assesment' => $isi->assesment->judul]),
            route('assesment-isi.index'),
        ));
    }
}
