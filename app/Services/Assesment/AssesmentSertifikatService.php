<?php

declare(strict_types=1);

namespace App\Services\Assesment;

use App\Models\Assesment\AssesmentIsi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generate sertifikat PDF A4 landscape setelah AssesmentIsi selesai diverifikasi - lihat
 * claude/fitur-assesment.md. Diupload ke S3 dengan pola object key yang sama seperti
 * KomoditasEntitasController::buildObjectKey() (folder entitas lalu bulan) supaya konsisten dan
 * mudah ditelusuri manual dari MinIO Console.
 */
class AssesmentSertifikatService
{
    public function generate(AssesmentIsi $isi): string
    {
        $isi->loadMissing(['assesment', 'entitas', 'komoditas', 'verifiedBy']);

        $pdf = Pdf::loadView('assesment-isi.sertifikat', ['isi' => $isi])
            ->setPaper('a4', 'landscape');

        $path = $this->buildObjectKey($isi);

        Storage::disk('s3')->put($path, $pdf->output());

        return $path;
    }

    private function buildObjectKey(AssesmentIsi $isi): string
    {
        $entitas = $isi->entitas;
        $entityFolder = $entitas->id.'-'.(Str::slug($entitas->name, '_') ?: 'entitas');
        $monthFolder = now()->format('Y-m');
        $filename = sprintf('sertifikat_%s_%s.pdf', Str::slug($isi->assesment->judul, '-') ?: 'assesment', now()->format('YmdHis'));

        return "assesment-isi/{$entityFolder}/{$monthFolder}/{$filename}";
    }
}
