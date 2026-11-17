<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaTangkap;

use App\Http\Controllers\Controller;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Services\KuotaTangkap\KuotaPeriodWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KuotaExportController extends Controller
{
    public function __construct(private readonly KuotaPeriodWorkflowService $workflowService) {}

    public function csv(KuotaPeriod $period): StreamedResponse
    {
        Gate::authorize('export', $period);
        $this->loadForExport($period);

        $filename = 'kuota-tangkap-'.$period->id.'-'.$period->year.'.csv';

        return response()->streamDownload(function () use ($period): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [__('Periode'), $period->label()]);
            fputcsv($out, [__('Status'), $period->status->label()]);
            fputcsv($out, [__('Kuota Ekspor (Q)'), (string) $period->kuota_tangkap]);
            fputcsv($out, [__('Snapshot'), $period->latestSnapshot !== null ? 'v'.$period->latestSnapshot->version : '-']);
            fputcsv($out, []);
            fputcsv($out, [
                __('Perusahaan'), __('Permohonan'), __('Realisasi Ekspor :tahun (E)', ['tahun' => $period->year - 1]), __('Nilai Evaluasi (N)'),
                __('Komponen Ekspor'), __('Komponen Evaluasi'), __('Rekomendasi'), __('Rekomendasi Bulat'),
                __('Ditetapkan'), __('Alasan Penyesuaian'), __('Realisasi Ekspor'), __('Nomor SK'), __('Tanggal SK'),
            ]);

            foreach ($period->allocations as $allocation) {
                fputcsv($out, [
                    $allocation->entitas?->name,
                    $allocation->permohonan,
                    $allocation->realisasi_ekspor,
                    $allocation->nilai_evaluasi,
                    $allocation->komponen_ekspor,
                    $allocation->komponen_evaluasi,
                    $allocation->rekomendasi,
                    $allocation->rekomendasi_bulat,
                    $allocation->ditetapkan,
                    $allocation->alasan_penyesuaian,
                    $allocation->realisasi_tangkap,
                    $allocation->sk_tangkap_nomor,
                    $allocation->sk_tangkap_tanggal?->toDateString(),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, [__('Cadangan Aktual'), $this->workflowService->cadanganAktual($period)]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(KuotaPeriod $period): Response
    {
        Gate::authorize('export', $period);
        $this->loadForExport($period);

        return Pdf::loadView('kuota-tangkap.pdf', [
            'period' => $period,
            'cadanganAktual' => $this->workflowService->cadanganAktual($period),
        ])->setPaper('a4', 'landscape')->download('kuota-tangkap-'.$period->id.'-'.$period->year.'.pdf');
    }

    private function loadForExport(KuotaPeriod $period): void
    {
        $period->load([
            'provinsi', 'komoditas', 'species', 'tradeCode', 'latestSnapshot', 'submitter', 'approver',
            'allocations' => fn ($query) => $query->with('entitas')->orderBy('id'),
        ]);
    }
}
