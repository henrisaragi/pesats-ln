<?php

declare(strict_types=1);

namespace App\Services\DokumenVerifikasi;

use Imagick;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Throwable;

class PdfTextExtractor
{
    /**
     * Di bawah ini dianggap "tidak ada text layer" (PDF hasil scan/gambar) - lihat
     * claude/otomatis-verifikasi.md poin "cek secara ocr".
     */
    private const MIN_TEXT_LAYER_LENGTH = 50;

    /**
     * Coba ambil teks lewat text layer PDF dulu (cepat, tanpa proses gambar) - kalau kosong atau
     * terlalu pendek (indikasi dokumen hasil scan tanpa text layer), baru fallback ke OCR lewat
     * Imagick (rasterisasi tiap halaman, butuh delegate Ghostscript) + Tesseract.
     */
    public function extract(string $absolutePath): string
    {
        $text = $this->extractTextLayer($absolutePath);

        if (mb_strlen(trim($text)) >= self::MIN_TEXT_LAYER_LENGTH) {
            return $text;
        }

        return $this->extractViaOcr($absolutePath);
    }

    private function extractTextLayer(string $absolutePath): string
    {
        try {
            return (new Parser)->parseFile($absolutePath)->getText();
        } catch (Throwable) {
            return '';
        }
    }

    private function extractViaOcr(string $absolutePath): string
    {
        $imagick = new Imagick;
        $imagick->setResolution(300, 300);

        try {
            $imagick->readImage($absolutePath);
        } catch (Throwable) {
            return '';
        }

        $languages = explode('+', (string) config('services.document_verification.ocr_lang', 'eng'));
        $text = '';

        foreach ($imagick as $page) {
            $page->setImageFormat('png');
            $page->setImageBackgroundColor('white');
            $page = $page->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

            $tempFile = tempnam(sys_get_temp_dir(), 'otomatis-verifikasi-ocr-').'.png';

            try {
                $page->writeImage($tempFile);
                $text .= "\n".(new TesseractOCR($tempFile))->lang(...$languages)->run();
            } catch (Throwable) {
                // Halaman ini gagal di-OCR, lanjut ke halaman berikutnya - hasil parsial masih
                // lebih berguna daripada menggagalkan seluruh pemeriksaan.
            } finally {
                @unlink($tempFile);
            }
        }

        return $text;
    }
}
