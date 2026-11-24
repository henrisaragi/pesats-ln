<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssesmentIsi;

use App\Enums\AssesmentIsiStatus;
use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssesmentIsi\AssesmentIsiVerifikasiJawabanRequest;
use App\Http\Requests\AssesmentIsi\AssesmentIsiVerifikasiRequest;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Assesment\AssesmentIsiJawaban;
use App\Models\Assesment\AssesmentJawaban;
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
            ->with(['assesment.pertanyaan.komoditas', 'assesment.pertanyaan.jawaban.komoditas', 'entitas', 'komoditas'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('assesment-isi.verifikasi.index', [
            'items' => $items,
            'hasil' => $items->getCollection()->mapWithKeys(fn (AssesmentIsi $isi): array => [$isi->id => $this->skorService->hasil($isi)]),
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

        $activities = $isi->activities()->with(['user', 'pertanyaan'])->latest('id')->limit(50)->get();

        return view('assesment-isi.verifikasi.show', [
            'isi' => $isi,
            'pertanyaan' => $pertanyaan,
            'isiJawaban' => $isiJawaban,
            'activities' => $activities,
        ]);
    }

    /**
     * Simpan koreksi verifikator untuk satu pertanyaan - dipanggil setiap tombol "Simpan & Lanjut"
     * pada wizard verifikasi, jadi progres tidak hilang kalau halaman ditinggal di tengah jalan.
     */
    public function saveJawaban(AssesmentIsiVerifikasiJawabanRequest $request, AssesmentIsi $isi, AssesmentPertanyaan $pertanyaan): JsonResponse
    {
        abort_unless($isi->bisaDiverifikasi(), 403, __('Assesment ini sudah tidak dapat diubah.'));

        $isiJawaban = $isi->jawaban()->where('pertanyaan_id', $pertanyaan->id)->firstOrFail();

        DB::transaction(fn () => $this->setJawabanVerifikator($isi, $isiJawaban, (int) $request->validated('jawaban_id')));

        return response()->json(['message' => __('Jawaban tersimpan.')]);
    }

    public function complete(AssesmentIsiVerifikasiRequest $request, AssesmentIsi $isi): RedirectResponse
    {
        abort_unless($isi->bisaDiverifikasi(), 403);

        $isRevisi = $isi->status === AssesmentIsiStatus::Verified;
        $sebelum = ['skor_total' => $isi->skor_total, 'sertifikat_path' => $isi->sertifikat_path];

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
            $isiJawabanRows = $isi->jawaban()->whereIn('pertanyaan_id', $pertanyaanIds)->get();

            foreach ($isiJawabanRows as $isiJawaban) {
                $this->setJawabanVerifikator($isi, $isiJawaban, (int) $jawaban[$isiJawaban->pertanyaan_id]);
            }
        });

        $skorTotal = $this->skorService->hitung($isi->fresh());

        $isi->update([
            'status' => AssesmentIsiStatus::Verified,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
            'skor_total' => $skorTotal,
        ]);

        // Sertifikat lama sengaja tidak dihapus dari S3 - path-nya tersimpan di activity log sebagai riwayat.
        $sertifikatPath = $this->sertifikatService->generate($isi->fresh());
        $isi->update(['sertifikat_path' => $sertifikatPath]);

        $isi->activities()->create([
            'user_id' => Auth::id(),
            'action' => $isRevisi ? 'direvisi' : 'diverifikasi',
            'old_values' => $isRevisi ? $sebelum : null,
            'new_values' => ['skor_total' => $isi->skor_total, 'sertifikat_path' => $sertifikatPath],
        ]);

        $this->notifySubmitter($isi, $isRevisi);

        return redirect()->route('assesment-isi.verifikasi.index', $isRevisi ? ['status' => AssesmentIsiStatus::Verified->value] : [])->with('success', $isRevisi
            ? __('Revisi verifikasi tersimpan dan sertifikat diterbitkan ulang.')
            : __('Assesment berhasil diverifikasi dan sertifikat diterbitkan.'));
    }

    /**
     * Ubah jawaban verifikator satu pertanyaan dan catat di activity log bila nilainya berubah.
     */
    private function setJawabanVerifikator(AssesmentIsi $isi, AssesmentIsiJawaban $isiJawaban, int $jawabanId): void
    {
        $lamaId = $isiJawaban->jawaban_verifikator_id;

        if ($lamaId === $jawabanId) {
            return;
        }

        $isiJawaban->update(['jawaban_verifikator_id' => $jawabanId]);

        $teks = AssesmentJawaban::query()->whereIn('id', array_filter([$lamaId, $jawabanId]))->pluck('teks_jawaban', 'id');

        $isi->activities()->create([
            'pertanyaan_id' => $isiJawaban->pertanyaan_id,
            'user_id' => Auth::id(),
            'action' => 'jawaban_diubah',
            'old_values' => $lamaId === null ? null : ['jawaban_id' => $lamaId, 'jawaban' => $teks->get($lamaId)],
            'new_values' => ['jawaban_id' => $jawabanId, 'jawaban' => $teks->get($jawabanId)],
        ]);
    }

    private function notifySubmitter(AssesmentIsi $isi, bool $isRevisi = false): void
    {
        $isi->loadMissing(['entitas.users', 'assesment']);

        $recipients = $isi->entitas?->users ?? collect();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AppNotification(
            NotificationCategory::Permohonan,
            $isRevisi ? __('Verifikasi Assesment Direvisi') : __('Assesment Terverifikasi'),
            $isRevisi
                ? __('Verifikasi assesment :assesment telah direvisi, sertifikat terbaru sudah dapat diunduh.', ['assesment' => $isi->assesment->judul])
                : __('Assesment :assesment telah diverifikasi, sertifikat sudah dapat diunduh.', ['assesment' => $isi->assesment->judul]),
            route('assesment-isi.index'),
        ));
    }
}
