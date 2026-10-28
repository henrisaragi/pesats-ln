<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SatsLn;
use App\Models\SatsLnPrint;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Facades\Storage;

/**
 * PDF resmi SATS-LN (resources/views/pdf/cites-export.blade.php) - dipakai untuk pratinjau di
 * modal Print/Cetak dan untuk membekukan hasil cetak ke sats_ln_prints.
 */
class SatsLnPdfService
{
    public const DISK = 's3';

    public function __construct(private readonly PermohonanService $permohonanService) {}

    public function render(SatsLn $satsLn, User $penandatangan, float $sisaTinggi): DomPdf
    {
        return Pdf::loadView('pdf.cites-export', $this->viewData($satsLn, $penandatangan, $sisaTinggi))->setPaper('a4');
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(SatsLn $satsLn, User $penandatangan, float $sisaTinggi): array
    {
        $satsLn->loadMissing([
            'permohonan.entitas',
            'customerCompany.country',
            'purposeOfTransaction',
            'portOfExportation',
            'portOfDestination',
            'items.species',
            'items.kuotaNasional.source',
            'items.kuotaNasional.tradeCode',
        ]);
        $entitas = $satsLn->permohonan->entitas;
        $customer = $satsLn->customerCompany;

        return [
            'satsLn' => $satsLn,
            'nomorsatln' => $satsLn->issuedNumberLabel(),
            'tipedoc' => $satsLn->type_satsln,
            'exportir' => collect([$entitas?->name, $entitas?->address, 'INDONESIA'])->filter()->implode(', '),
            'importir' => collect([$customer?->name, $customer?->address, $customer?->country?->name])->filter()->implode(', '),
            'date_valid' => $satsLn->valid_until?->format('d F Y') ?? '',
            'port_destination' => $satsLn->portOfDestination?->name ?? '',
            'port_departur' => $satsLn->portOfExportation?->name ?? '',
            'purpose' => $satsLn->purposeOfTransaction?->name ?? '',
            'publication_date' => $satsLn->publish_date?->format('d F Y') ?? '',
            'ttd' => $penandatangan,
            'sisaTinggi' => $sisaTinggi,
            'realisasiNasionalRunningTotalByItemId' => $this->permohonanService->realisasiKuotaNasionalRunningTotalForItems($satsLn->items),
        ];
    }

    /**
     * Simpan PDF hasil render sekarang beserta pengaturannya, supaya yang dicetak tetap sama
     * walau data SATS-LN berubah setelahnya.
     */
    public function store(SatsLn $satsLn, User $penandatangan, float $sisaTinggi, User $printedBy): SatsLnPrint
    {
        $path = 'sats-ln-prints/'.$satsLn->public_id.'/'.now()->format('YmdHis').'-'.str()->random(6).'.pdf';
        Storage::disk(self::DISK)->put($path, $this->render($satsLn, $penandatangan, $sisaTinggi)->output());

        return $satsLn->prints()->create([
            'penandatangan_id' => $penandatangan->id,
            'printed_by_id' => $printedBy->id,
            'sisa_tinggi' => $sisaTinggi,
            'pdf_path' => $path,
        ]);
    }

    public function filename(SatsLn $satsLn): string
    {
        return 'SATS-LN-'.($satsLn->satsln_number_issued ?? $satsLn->nomor_urut).'.pdf';
    }
}
