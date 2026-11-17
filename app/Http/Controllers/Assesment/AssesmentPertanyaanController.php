<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assesment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assesment\AssesmentPertanyaanRequest;
use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentAspek;
use App\Models\Assesment\AssesmentPertanyaan;
use App\Models\Komoditas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssesmentPertanyaanController extends Controller
{
    public function index(Assesment $assesment): View
    {
        $assesment->load([
            'pertanyaan' => fn ($query) => $query->orderBy('urutan'),
            'pertanyaan.aspek',
            'pertanyaan.komoditas',
            'pertanyaan.jawaban.komoditas',
            'pertanyaan.buktiDukung',
        ]);

        return view('assesment.pertanyaan.index', [
            'assesment' => $assesment,
            'komoditasOptions' => Komoditas::query()->orderBy('name')->get(),
        ]);
    }

    public function store(AssesmentPertanyaanRequest $request, Assesment $assesment): RedirectResponse
    {
        $this->abortIfHasSubmissions($assesment);

        DB::transaction(function () use ($request, $assesment): void {
            $pertanyaan = AssesmentPertanyaan::create([
                'assesment_id' => $assesment->id,
                'aspek_id' => $this->resolveAspekId($request),
                'pertanyaan' => $request->validated('pertanyaan'),
                'informasi' => $request->validated('informasi'),
                'metode_verifikasi' => $request->validated('metode_verifikasi'),
                'urutan' => $assesment->pertanyaan()->count(),
                'created_by' => Auth::id(),
            ]);

            $this->syncKomoditasBobot($pertanyaan, $request);
            $this->syncJawaban($pertanyaan, $request);
            $this->syncBuktiDukung($pertanyaan, $request);
        });

        return redirect()->route('assesment.pertanyaan.index', $assesment)
            ->with('success', __('Pertanyaan berhasil ditambahkan.'));
    }

    public function edit(Assesment $assesment, AssesmentPertanyaan $pertanyaan): View
    {
        $this->abortIfHasSubmissions($assesment);
        abort_unless($pertanyaan->assesment_id === $assesment->id, 404);

        $pertanyaan->load(['aspek', 'komoditas', 'jawaban.komoditas', 'buktiDukung']);

        return view('assesment.pertanyaan.edit', [
            'assesment' => $assesment,
            'pertanyaan' => $pertanyaan,
            'komoditasOptions' => Komoditas::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Jawaban & bukti dukung diganti total (hapus lalu buat ulang) - aman karena
     * abortIfHasSubmissions() menjamin belum ada assesment_isi yang mereferensikannya.
     */
    public function update(AssesmentPertanyaanRequest $request, Assesment $assesment, AssesmentPertanyaan $pertanyaan): RedirectResponse
    {
        $this->abortIfHasSubmissions($assesment);
        abort_unless($pertanyaan->assesment_id === $assesment->id, 404);

        DB::transaction(function () use ($request, $pertanyaan): void {
            $pertanyaan->update([
                'aspek_id' => $this->resolveAspekId($request),
                'pertanyaan' => $request->validated('pertanyaan'),
                'informasi' => $request->validated('informasi'),
                'metode_verifikasi' => $request->validated('metode_verifikasi'),
            ]);

            $pertanyaan->jawaban()->delete();
            $pertanyaan->buktiDukung()->delete();

            $this->syncKomoditasBobot($pertanyaan, $request);
            $this->syncJawaban($pertanyaan, $request);
            $this->syncBuktiDukung($pertanyaan, $request);
        });

        return redirect()->route('assesment.pertanyaan.index', $assesment)
            ->with('success', __('Pertanyaan berhasil diperbarui.'));
    }

    public function destroy(Assesment $assesment, AssesmentPertanyaan $pertanyaan): RedirectResponse
    {
        $this->abortIfHasSubmissions($assesment);
        abort_unless($pertanyaan->assesment_id === $assesment->id, 404);

        $pertanyaan->delete();

        return redirect()->route('assesment.pertanyaan.index', $assesment)
            ->with('success', __('Pertanyaan berhasil dihapus.'));
    }

    /**
     * Struktur pertanyaan (komoditas/bobot, jawaban/skor, bukti dukung) dikunci begitu ada
     * entitas yang sudah mulai mengisi assesment ini, supaya tidak merusak skor pengajuan yang
     * sedang berjalan.
     */
    private function abortIfHasSubmissions(Assesment $assesment): void
    {
        abort_if($assesment->isi()->exists(), 403, __('Assesment ini sudah memiliki pengajuan entitas, struktur pertanyaan tidak dapat diubah.'));
    }

    /**
     * Aspek dicari case-insensitive, dibuat baru bila belum ada.
     */
    private function resolveAspekId(AssesmentPertanyaanRequest $request): int
    {
        $nama = trim($request->validated('aspek_nama'));

        $aspek = AssesmentAspek::query()
            ->whereRaw('lower(nama) = ?', [Str::lower($nama)])
            ->first();

        $aspek ??= AssesmentAspek::create([
            'nama' => $nama,
            'created_by' => Auth::id(),
        ]);

        return $aspek->id;
    }

    private function syncKomoditasBobot(AssesmentPertanyaan $pertanyaan, AssesmentPertanyaanRequest $request): void
    {
        $bobot = (array) $request->validated('bobot');
        $sync = [];

        foreach ((array) $request->validated('komoditas_id') as $komoditasId) {
            $sync[(int) $komoditasId] = ['bobot' => $bobot[$komoditasId]];
        }

        $pertanyaan->komoditas()->sync($sync);
    }

    /**
     * Field skor dirender untuk SEMUA komoditas di form (disembunyikan via CSS untuk yang tidak
     * dicentang, bukan dihapus dari DOM), jadi $row['skor'] ikut memuat entry kosong untuk
     * komoditas yang tidak dicentang - disaring ke $komoditasIds (checked list) supaya tidak ikut
     * di-sync dengan nilai null/kosong (lihat AssesmentPertanyaanRequest::rules()).
     */
    private function syncJawaban(AssesmentPertanyaan $pertanyaan, AssesmentPertanyaanRequest $request): void
    {
        $komoditasIds = array_map('intval', (array) $request->validated('komoditas_id'));

        foreach ((array) $request->validated('jawaban') as $index => $row) {
            $jawaban = $pertanyaan->jawaban()->create([
                'teks_jawaban' => $row['teks'],
                'urutan' => $index,
            ]);

            $sync = [];

            foreach ((array) $row['skor'] as $komoditasId => $skor) {
                if (in_array((int) $komoditasId, $komoditasIds, true) && $skor !== null && $skor !== '') {
                    $sync[(int) $komoditasId] = ['skor' => $skor];
                }
            }

            $jawaban->komoditas()->sync($sync);
        }
    }

    private function syncBuktiDukung(AssesmentPertanyaan $pertanyaan, AssesmentPertanyaanRequest $request): void
    {
        foreach ((array) $request->validated('bukti_dukung') as $index => $label) {
            $pertanyaan->buktiDukung()->create([
                'label' => $label,
                'urutan' => $index,
            ]);
        }
    }
}
