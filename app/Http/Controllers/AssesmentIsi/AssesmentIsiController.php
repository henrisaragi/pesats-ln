<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssesmentIsi;

use App\Enums\AssesmentIsiStatus;
use App\Enums\FiturPanduan;
use App\Enums\KomoditasEntitasStatus;
use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssesmentIsi\AssesmentIsiJawabanRequest;
use App\Http\Requests\AssesmentIsi\AssesmentIsiStoreRequest;
use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Assesment\AssesmentIsiBukti;
use App\Models\Entitas\Entitas;
use App\Models\KomoditasEntitas;
use App\Models\Panduan\TemplateSuratPernyataan;
use App\Models\Panduan\VideoPanduan;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Assesment\AssesmentSkorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssesmentIsiController extends Controller
{
    public function __construct(private readonly AssesmentSkorService $skorService) {}

    public function index(): View
    {
        $entity = $this->ownEntityOrAbort();

        $verifiedKomoditasIds = KomoditasEntitas::query()
            ->where('entitas_id', $entity->id)
            ->where('status', KomoditasEntitasStatus::Verified)
            ->pluck('komoditas_id');

        $pairs = Assesment::query()
            ->whereHas('pertanyaan.komoditas', fn ($query) => $query->whereIn('komoditas.id', $verifiedKomoditasIds))
            ->with(['pertanyaan.komoditas' => fn ($query) => $query->whereIn('komoditas.id', $verifiedKomoditasIds)])
            ->get()
            ->flatMap(fn (Assesment $assesment) => $assesment->pertanyaan
                ->flatMap(fn ($pertanyaan) => $pertanyaan->komoditas)
                ->unique('id')
                ->map(fn ($komoditas) => ['assesment' => $assesment, 'komoditas' => $komoditas])
            )
            ->unique(fn (array $row) => $row['assesment']->id.'-'.$row['komoditas']->id)
            ->values();

        $existing = AssesmentIsi::query()
            ->where('entitas_id', $entity->id)
            ->get()
            ->keyBy(fn (AssesmentIsi $isi) => $isi->assesment_id.'-'.$isi->komoditas_id);

        $rows = $pairs->map(fn (array $row) => [
            'assesment' => $row['assesment'],
            'komoditas' => $row['komoditas'],
            'isi' => $existing->get($row['assesment']->id.'-'.$row['komoditas']->id),
        ]);

        return view('assesment-isi.index', [
            'rows' => $rows,
            'videoPanduan' => VideoPanduan::query()->untukFitur(FiturPanduan::AssesmentIsi)->oldest()->get(),
            'templateSuratPernyataan' => TemplateSuratPernyataan::query()->untukFitur(FiturPanduan::AssesmentIsi)->oldest()->get(),
        ]);
    }

    /**
     * Dipanggil dari modal "Ikuti Assesment" (assesment-isi/partials/ikuti-modal.blade.php) yang
     * mewajibkan upload Surat Pernyataan sebelum draft dibuat.
     */
    public function store(AssesmentIsiStoreRequest $request): RedirectResponse
    {
        $entity = $this->ownEntityOrAbort();
        $data = $request->validated();

        $isKomoditasVerified = KomoditasEntitas::query()
            ->where('entitas_id', $entity->id)
            ->where('komoditas_id', $data['komoditas_id'])
            ->where('status', KomoditasEntitasStatus::Verified)
            ->exists();

        abort_unless($isKomoditasVerified, 403, __('Komoditas ini belum terverifikasi untuk entitas Anda.'));

        $isi = AssesmentIsi::query()
            ->where('assesment_id', $data['assesment_id'])
            ->where('entitas_id', $entity->id)
            ->where('komoditas_id', $data['komoditas_id'])
            ->first();

        if ($isi === null) {
            $file = $request->file('surat_pernyataan');
            $path = $this->buildObjectKey($entity, 'surat-pernyataan', $file->extension() ?: 'pdf');

            Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

            $isi = AssesmentIsi::create([
                'assesment_id' => $data['assesment_id'],
                'entitas_id' => $entity->id,
                'komoditas_id' => $data['komoditas_id'],
                'status' => AssesmentIsiStatus::Draft,
                'surat_pernyataan_path' => $path,
                'surat_pernyataan_filename' => $file->getClientOriginalName(),
                'created_by' => Auth::id(),
            ]);
        }

        return redirect()->route('assesment-isi.show', $isi);
    }

    public function previewSuratPernyataan(AssesmentIsi $isi): RedirectResponse
    {
        $this->authorizeAccess($isi);

        abort_if($isi->surat_pernyataan_path === null, 404);

        return redirect(Storage::disk('s3')->temporaryUrl($isi->surat_pernyataan_path, now()->addMinutes(10)));
    }

    public function show(AssesmentIsi $isi): View
    {
        $this->authorizeAccess($isi);

        $isi->load(['assesment', 'komoditas', 'entitas', 'bukti.buktiDukung']);

        $pertanyaan = $isi->assesment->pertanyaan()
            ->whereHas('komoditas', fn ($query) => $query->where('komoditas.id', $isi->komoditas_id))
            ->with(['aspek', 'jawaban', 'buktiDukung'])
            ->orderBy('urutan')
            ->get();

        // Jawaban entitas dan koreksi verifikator TETAP dua kolom terpisah (jawaban_entitas_id
        // vs jawaban_verifikator_id) - keyBy pertanyaan_id di sini supaya blade bisa menampilkan
        // keduanya berdampingan, bukan yang satu menimpa yang lain.
        $isiJawaban = $isi->jawaban()->with('jawabanVerifikator')->get()->keyBy('pertanyaan_id');
        $buktiTersimpan = $isi->bukti()->get()->keyBy('bukti_dukung_id');

        return view('assesment-isi.show', [
            'isi' => $isi,
            'pertanyaan' => $pertanyaan,
            'isiJawaban' => $isiJawaban,
            'buktiTersimpan' => $buktiTersimpan,
        ]);
    }

    public function update(AssesmentIsiJawabanRequest $request, AssesmentIsi $isi): RedirectResponse
    {
        $this->authorizeOwner($isi);
        $this->abortUnlessDraft($isi);

        $this->saveJawaban($isi, (array) $request->validated('jawaban'));

        return redirect()->route('assesment-isi.show', $isi)->with('success', __('Jawaban berhasil disimpan sebagai draft.'));
    }

    /**
     * Submit menyimpan jawaban yang dikirim bersamaan request ini (form yang sama dengan
     * "Simpan Draft", tombolnya cuma beda formaction - lihat assesment-isi/show.blade.php) SEBELUM
     * memvalidasi kelengkapan, supaya entitas bisa langsung klik "Submit Assesment" tanpa wajib
     * "Simpan Draft" dulu.
     */
    public function submit(AssesmentIsiJawabanRequest $request, AssesmentIsi $isi): RedirectResponse
    {
        $this->authorizeOwner($isi);
        $this->abortUnlessDraft($isi);

        $this->saveJawaban($isi, (array) $request->validated('jawaban'));

        // Bukti dukung sengaja tidak diwajibkan saat submit (permintaan user) - cuma jawaban yang
        // wajib lengkap untuk semua pertanyaan komoditas ini.
        $pertanyaanCount = $isi->assesment->pertanyaan()
            ->whereHas('komoditas', fn ($query) => $query->where('komoditas.id', $isi->komoditas_id))
            ->count();

        $jawabanCount = $isi->jawaban()->whereNotNull('jawaban_entitas_id')->count();

        if ($jawabanCount < $pertanyaanCount) {
            throw ValidationException::withMessages([
                'submit' => __('Semua pertanyaan harus dijawab sebelum submit.'),
            ]);
        }

        $isi->update([
            'status' => AssesmentIsiStatus::Submitted,
            'submitted_at' => now(),
            // Skor dari jawaban entitas sendiri (belum ada koreksi verifikator) - dibekukan di
            // sini supaya tetap bisa dibandingkan dengan skor_total setelah verifikasi, lihat
            // AssesmentSkorService.
            'skor_entitas' => $this->skorService->hitung($isi->fresh(), gunakanKoreksiVerifikator: false),
        ]);

        $this->notifyVerifiers($isi);

        return redirect()->route('assesment-isi.index')->with('success', __('Assesment berhasil disubmit, menunggu verifikasi.'));
    }

    private function saveJawaban(AssesmentIsi $isi, array $jawaban): void
    {
        $pertanyaanIds = $isi->assesment->pertanyaan()
            ->whereHas('komoditas', fn ($query) => $query->where('komoditas.id', $isi->komoditas_id))
            ->pluck('id');

        DB::transaction(function () use ($jawaban, $isi, $pertanyaanIds): void {
            foreach ($jawaban as $pertanyaanId => $jawabanId) {
                if (! $pertanyaanIds->contains((int) $pertanyaanId)) {
                    continue;
                }

                $isi->jawaban()->updateOrCreate(
                    ['pertanyaan_id' => $pertanyaanId],
                    ['jawaban_entitas_id' => $jawabanId]
                );
            }
        });
    }

    public function uploadBukti(Request $request, AssesmentIsi $isi, int $buktiDukungId): JsonResponse
    {
        $this->authorizeOwner($isi);

        if ($isi->status !== AssesmentIsiStatus::Draft) {
            return response()->json(['message' => __('Assesment ini sudah tidak dapat diubah.')], 403);
        }

        $buktiDukung = $isi->assesment->pertanyaan()
            ->whereHas('komoditas', fn ($query) => $query->where('komoditas.id', $isi->komoditas_id))
            ->whereHas('buktiDukung', fn ($query) => $query->where('assesment_bukti_dukung.id', $buktiDukungId))
            ->firstOrFail()
            ->buktiDukung()
            ->findOrFail($buktiDukungId);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $this->buildObjectKey($isi->entitas, $buktiDukung->label, $file->extension() ?: 'pdf');

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

        $existing = $isi->bukti()->where('bukti_dukung_id', $buktiDukungId)->first();
        $oldPath = $existing?->file_path;

        $bukti = DB::transaction(function () use ($isi, $buktiDukungId, $path, $file) {
            return AssesmentIsiBukti::updateOrCreate(
                ['isi_id' => $isi->id, 'bukti_dukung_id' => $buktiDukungId],
                ['file_path' => $path, 'original_filename' => $file->getClientOriginalName()]
            );
        });

        if ($oldPath !== null && $oldPath !== $path) {
            Storage::disk('s3')->delete($oldPath);
        }

        return response()->json([
            'file_path' => $bukti->file_path,
            'original_filename' => $bukti->original_filename,
            'preview_url' => route('assesment-isi.bukti.preview', $bukti),
        ]);
    }

    public function previewBukti(AssesmentIsiBukti $bukti): RedirectResponse
    {
        // authorizeAccess() (bukan authorizeOwner()) - route ini juga dibuka untuk verifikator
        // lewat middleware permission:assesment-isi.verify (lihat routes/assesment-isi.php serta
        // pemakaiannya di assesment-isi/verifikasi/show.blade.php), authorizeOwner() yang cuma
        // izinkan pemilik entitas bikin verifikator selalu 403 saat pratinjau bukti dukung.
        $this->authorizeAccess($bukti->isi);

        $url = Storage::disk('s3')->temporaryUrl($bukti->file_path, now()->addMinutes(10));

        return redirect($url);
    }

    private function notifyVerifiers(AssesmentIsi $isi): void
    {
        $isi->loadMissing(['assesment', 'entitas', 'komoditas']);

        $verifiers = User::permission('assesment-isi.verify')->get();

        if ($verifiers->isEmpty()) {
            return;
        }

        Notification::send($verifiers, new AppNotification(
            NotificationCategory::Permohonan,
            __('Assesment Baru Disubmit'),
            __(':entitas mengajukan :assesment untuk komoditas :komoditas.', [
                'entitas' => $isi->entitas->name,
                'assesment' => $isi->assesment->judul,
                'komoditas' => $isi->komoditas->name,
            ]),
            route('assesment-isi.verifikasi.index'),
        ));
    }

    private function authorizeAccess(AssesmentIsi $isi): void
    {
        $user = Auth::user();
        $isOwner = $user->entity_id !== null && $user->entity_id === $isi->entitas_id;

        abort_unless($isOwner || $user->can('assesment-isi.verify') || $user->can('assesment-isi.view-all'), 403);
    }

    private function authorizeOwner(AssesmentIsi $isi): void
    {
        $user = Auth::user();

        abort_unless($user->entity_id !== null && $user->entity_id === $isi->entitas_id, 403);
    }

    private function abortUnlessDraft(AssesmentIsi $isi): void
    {
        abort_unless($isi->status === AssesmentIsiStatus::Draft, 403);
    }

    private function ownEntityOrAbort(): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless($entity !== null, 403);

        return $entity;
    }

    /**
     * Object key di bucket S3: folder {id}-{nama_entitas}/{tahun-bulan}, nama file dari label
     * bukti dukung + timestamp - pola sama seperti KomoditasEntitasController::buildObjectKey().
     */
    private function buildObjectKey(Entitas $entitas, string $label, string $extension): string
    {
        $slugLabel = Str::slug($label, '-') ?: 'bukti';
        $filename = sprintf('%s_%s.%s', $slugLabel, now()->format('YmdHis'), $extension);
        $entityFolder = $entitas->id.'-'.(Str::slug($entitas->name, '_') ?: 'entitas');
        $monthFolder = now()->format('Y-m');

        return "assesment-isi/{$entityFolder}/{$monthFolder}/{$filename}";
    }
}
