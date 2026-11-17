<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\AssesmentIsiStatus;
use App\Enums\KuotaPeriodStatus;
use App\Enums\SatsLnStatus;
use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KuotaNasional;
use App\Models\KuotaTangkap\KuotaAllocation;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Models\Provinsi;
use App\Models\PurposeOfTransaction;
use App\Models\SatsLn;
use App\Models\SatsLnItem;
use App\Models\SourceStatus;
use App\Models\Species;
use App\Models\TradeCode;
use App\Models\User;

/**
 * Data uji Pembagian Kuota Ekspor. Fixture Excel "Formula 2026 kulit reptil" (Biawak Air,
 * Kulit, Jatim 2026, Q = 17.000) - lihat docs/PRD-pembagian-kuota-tangkap.md §11.
 */
trait KuotaTangkapFixtures
{
    /**
     * Nama => [permohonan, E_i, N_i].
     *
     * @var array<string, array{0: int, 1: int, 2: string}>
     */
    protected const EXCEL_FIXTURE = [
        'PT Ahmad Jaya Sentosa' => [5000, 120, '0.63'],
        'PT. Berkat Alam Abadi' => [16150, 31373, '0.54'],
        'PT Dua Saudara Sakti Berkah' => [3000, 120, '0.24'],
        'PT. Fendibali Leather Indonesia' => [37000, 29600, '0.88'],
        'CV. Banyuwangi Leather' => [2000, 0, '0.77'],
    ];

    /**
     * @param  list<string>  $permissions
     */
    protected function makeKuotaUser(array $permissions, ?Entitas $entity = null, string $name = 'Petugas'): User
    {
        $user = User::create([
            'name' => $name,
            'email' => uniqid('kuota', true).'@example.test',
            'username' => uniqid('kuota', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
            'entity_id' => $entity?->id,
        ]);

        $user->givePermissionTo($permissions);

        return $user;
    }

    protected function makeProvinsi(string $name = 'Jawa Timur'): Provinsi
    {
        return Provinsi::create(['code' => (string) random_int(10, 99999), 'name' => $name]);
    }

    protected function makePerusahaan(string $name, ?Provinsi $provinsi = null): Entitas
    {
        return Entitas::create(['entity_type' => 'perusahaan', 'name' => $name, 'provinsi_id' => $provinsi?->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeKuotaPeriod(array $attributes = []): KuotaPeriod
    {
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus salvator', 'trade_status' => 'II']);
        $tradeCode = TradeCode::create(['name' => 'Kulit', 'code' => 'SKI-'.uniqid(), 'unit' => 'pcs']);

        return KuotaPeriod::create([
            'provinsi_id' => $attributes['provinsi_id'] ?? $this->makeProvinsi()->id,
            'komoditas_id' => $komoditas->id,
            'species_id' => $species->id,
            'trade_code_id' => $tradeCode->id,
            'year' => 2026,
            'kuota_tangkap' => '17000',
            'status' => KuotaPeriodStatus::Draft,
            ...$attributes,
        ]);
    }

    protected function addParticipant(KuotaPeriod $period, Entitas $entitas, ?string $permohonan = null): KuotaAllocation
    {
        return $period->allocations()->create(['entity_id' => $entitas->id, 'permohonan' => $permohonan]);
    }

    protected function giveVerifiedAssesment(Entitas $entitas, int $komoditasId, string $skorTotal, string $verifiedAt = '2026-01-10 08:00:00'): AssesmentIsi
    {
        $assesment = Assesment::create(['judul' => 'Assesment '.uniqid()]);

        return AssesmentIsi::create([
            'assesment_id' => $assesment->id,
            'entitas_id' => $entitas->id,
            'komoditas_id' => $komoditasId,
            'status' => AssesmentIsiStatus::Verified,
            'verified_at' => $verifiedAt,
            'skor_total' => $skorTotal,
        ]);
    }

    /**
     * @param  array<string, mixed>  $satsLnOverrides
     */
    protected function giveRealisasiEkspor(Entitas $entitas, KuotaPeriod $period, string $realisasi, array $satsLnOverrides = [], ?int $tradeCodeId = null): SatsLnItem
    {
        $year = $satsLnOverrides['year'] ?? $period->year - 1;

        $kuotaNasional = KuotaNasional::create([
            'komoditas_id' => $period->komoditas_id,
            'species_id' => $period->species_id,
            'source_id' => SourceStatus::create(['code' => 'W'.random_int(1000, 999999), 'description' => 'Wild'])->id,
            'trade_code_id' => $tradeCodeId ?? $period->trade_code_id,
            'quota' => 100000,
            'year' => $year,
        ]);

        $satsLn = SatsLn::create([
            'nomor_urut' => 1,
            'entity_id' => $entitas->id,
            'type_satsln' => 'Export',
            'purpose_of_transaction_id' => PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial'])->id,
            'satsln_issued_type' => 'P_0',
            'satsln_number_issued' => uniqid('SN', true),
            'status_satsln' => SatsLnStatus::Disetujui->value,
            ...$satsLnOverrides,
            'year' => $year,
        ]);

        return SatsLnItem::create([
            'sats_ln_id' => $satsLn->id,
            'kuota_nasional_id' => $kuotaNasional->id,
            'species_id' => $period->species_id,
            'qty' => $realisasi,
            'realisasi_on_port' => $realisasi,
        ]);
    }

    /**
     * Periode Excel lengkap: 5 perusahaan dengan permohonan, realisasi ekspor 2025, dan assessment.
     *
     * @return array{0: KuotaPeriod, 1: array<string, Entitas>}
     */
    protected function makeExcelFixturePeriod(): array
    {
        $period = $this->makeKuotaPeriod();
        $entities = [];

        foreach (self::EXCEL_FIXTURE as $name => [$permohonan, $realisasi, $nilai]) {
            $entitas = $this->makePerusahaan($name);
            $entities[$name] = $entitas;
            $this->addParticipant($period, $entitas, (string) $permohonan);
            $this->giveVerifiedAssesment($entitas, $period->komoditas_id, $nilai);

            if ($realisasi > 0) {
                $this->giveRealisasiEkspor($entitas, $period, (string) $realisasi);
            }
        }

        return [$period, $entities];
    }
}
