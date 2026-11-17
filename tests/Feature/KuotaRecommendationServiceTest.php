<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssesmentIsiStatus;
use App\Enums\KuotaPeriodStatus;
use App\Enums\SatsLnStatus;
use App\Models\Assesment\AssesmentIsi;
use App\Models\KuotaTangkap\KuotaActivity;
use App\Models\KuotaTangkap\KuotaSetting;
use App\Models\SatsLn;
use App\Models\TradeCode;
use App\Services\KuotaTangkap\KuotaRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\KuotaTangkapFixtures;
use Tests\TestCase;

class KuotaRecommendationServiceTest extends TestCase
{
    use KuotaTangkapFixtures;
    use RefreshDatabase;

    private function service(): KuotaRecommendationService
    {
        return app(KuotaRecommendationService::class);
    }

    public function test_calculates_excel_fixture_from_realisasi_and_assesment_tables(): void
    {
        [$period, $entities] = $this->makeExcelFixturePeriod();
        $user = $this->makeKuotaUser([]);

        $snapshot = $this->service()->calculate($period, $user);

        $this->assertSame(1, $snapshot->version);
        $this->assertSame(15300, $snapshot->kuota_dibagi);
        $this->assertSame('61213.0000', (string) $snapshot->total_realisasi_ekspor);
        $this->assertSame('3.06', (string) $snapshot->total_nilai_evaluasi);
        $this->assertSame(2025, $snapshot->tahun_realisasi);
        $this->assertSame([], $snapshot->warnings);

        $allocations = $period->allocations()->get()->keyBy('entity_id');
        $expected = [
            'PT Ahmad Jaya Sentosa' => [2214, '120.0000', '0.63'],
            'PT. Berkat Alam Abadi' => [4242, '31373.0000', '0.54'],
            'PT Dua Saudara Sakti Berkah' => [849, '120.0000', '0.24'],
            'PT. Fendibali Leather Indonesia' => [5300, '29600.0000', '0.88'],
            'CV. Banyuwangi Leather' => [2695, '0.0000', '0.77'],
        ];

        foreach ($expected as $name => [$bulat, $e, $n]) {
            $allocation = $allocations[$entities[$name]->id];
            $this->assertSame($bulat, $allocation->rekomendasi_bulat, $name);
            $this->assertSame($e, (string) $allocation->realisasi_ekspor, $name);
            $this->assertSame($n, (string) $allocation->nilai_evaluasi, $name);
            $this->assertNull($allocation->ditetapkan, $name);
        }

        $this->assertSame(KuotaPeriodStatus::Dihitung, $period->refresh()->status);
        $this->assertSame($snapshot->id, $period->latest_snapshot_id);
        $this->assertTrue(KuotaActivity::query()->where('kuota_period_id', $period->id)->where('action', 'calculated')->exists());
    }

    public function test_realisasi_only_counts_approved_exports_of_previous_year_for_same_species_and_trade_code(): void
    {
        $period = $this->makeKuotaPeriod();
        $entitas = $this->makePerusahaan('PT Uji');
        $this->addParticipant($period, $entitas);
        $this->giveVerifiedAssesment($entitas, $period->komoditas_id, '1.00');

        $this->giveRealisasiEkspor($entitas, $period, '100');
        $this->giveRealisasiEkspor($entitas, $period, '1', ['year' => 2026]);
        $this->giveRealisasiEkspor($entitas, $period, '2', ['type_satsln' => 'Import']);
        $this->giveRealisasiEkspor($entitas, $period, '4', ['status_satsln' => SatsLnStatus::Ditolak->value]);
        $this->giveRealisasiEkspor($entitas, $period, '8', [], TradeCode::create(['name' => 'Hidup', 'code' => 'LIV-'.uniqid(), 'unit' => 'ekor'])->id);
        SatsLn::query()->findOrFail($this->giveRealisasiEkspor($entitas, $period, '16')->sats_ln_id)->delete();

        $this->service()->calculate($period, $this->makeKuotaUser([]));

        $this->assertSame('100.0000', (string) $period->allocations()->first()->realisasi_ekspor);
    }

    public function test_uses_latest_verified_assesment_and_warns_when_missing(): void
    {
        $period = $this->makeKuotaPeriod();
        $denganNilai = $this->makePerusahaan('PT Dinilai');
        $tanpaNilai = $this->makePerusahaan('PT Belum Dinilai');
        $this->addParticipant($period, $denganNilai);
        $this->addParticipant($period, $tanpaNilai);
        $this->giveVerifiedAssesment($denganNilai, $period->komoditas_id, '0.40', '2025-01-01 00:00:00');
        $terbaru = $this->giveVerifiedAssesment($denganNilai, $period->komoditas_id, '0.90', '2026-02-01 00:00:00');
        AssesmentIsi::query()->findOrFail($this->giveVerifiedAssesment($tanpaNilai, $period->komoditas_id, '5.00')->id)
            ->update(['status' => AssesmentIsiStatus::Submitted]);

        $snapshot = $this->service()->calculate($period, $this->makeKuotaUser([]));

        $allocation = $period->allocations()->where('entity_id', $denganNilai->id)->first();
        $this->assertSame('0.90', (string) $allocation->nilai_evaluasi);
        $this->assertSame($terbaru->id, $allocation->assesment_isi_id);
        $this->assertSame('0.00', (string) $period->allocations()->where('entity_id', $tanpaNilai->id)->first()->nilai_evaluasi);
        $this->assertContains(['code' => 'no_assesment', 'entity' => 'PT Belum Dinilai'], $snapshot->warnings);
        $this->assertContains(['code' => 'sum_e_zero'], $snapshot->warnings);
    }

    public function test_recalculation_creates_new_snapshot_keeps_old_one_and_keeps_ditetapkan(): void
    {
        [$period] = $this->makeExcelFixturePeriod();
        $user = $this->makeKuotaUser([]);
        $first = $this->service()->calculate($period, $user);
        $period->allocations()->first()->update(['ditetapkan' => '1000']);

        KuotaSetting::current()->update(['cadangan' => '0.0000']);
        $second = $this->service()->calculate($period->refresh(), $user);

        $this->assertSame(2, $second->version);
        $this->assertSame(17000, $second->kuota_dibagi);
        $this->assertSame(15300, $first->refresh()->kuota_dibagi);
        $this->assertSame('0.1000', (string) $first->cadangan);
        $this->assertSame(2460, $period->allocations()->orderBy('id')->first()->rekomendasi_bulat);
        $this->assertSame('1000.0000', (string) $period->allocations()->orderBy('id')->first()->ditetapkan);
    }

    public function test_rejects_calculation_without_participants(): void
    {
        $period = $this->makeKuotaPeriod();

        $this->expectException(ValidationException::class);

        $this->service()->calculate($period, $this->makeKuotaUser([]));
    }

    public function test_rejects_calculation_of_locked_period(): void
    {
        [$period] = $this->makeExcelFixturePeriod();
        $period->update(['status' => KuotaPeriodStatus::Ditetapkan]);

        $this->expectException(ValidationException::class);

        $this->service()->calculate($period, $this->makeKuotaUser([]));
    }
}
