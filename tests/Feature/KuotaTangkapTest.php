<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KuotaPeriodStatus;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KuotaTangkap\KuotaActivity;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Models\KuotaTangkap\KuotaSetting;
use App\Models\Species;
use App\Models\TradeCode;
use App\Models\User;
use Database\Seeders\KuotaTangkapPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\KuotaTangkapFixtures;
use Tests\TestCase;

class KuotaTangkapTest extends TestCase
{
    use KuotaTangkapFixtures;
    use RefreshDatabase;

    private const STAFF_PERMISSIONS = [
        'kuota.view', 'kuota.create', 'kuota.update', 'kuota.delete', 'kuota.calculate',
        'kuota.allocate', 'kuota.submit', 'kuota.realisasi', 'kuota.export',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(KuotaTangkapPermissionSeeder::class);
    }

    private function staff(): User
    {
        return $this->makeKuotaUser(self::STAFF_PERMISSIONS, null, 'Bidang Wilayah');
    }

    private function approver(): User
    {
        return $this->makeKuotaUser(['kuota.view', 'kuota.approve'], null, 'Pejabat Penetap');
    }

    /**
     * Periode Excel yang sudah dihitung lewat endpoint.
     */
    private function calculatedPeriod(User $staff): KuotaPeriod
    {
        [$period] = $this->makeExcelFixturePeriod();
        $this->actingAs($staff)->post(route('kuota-tangkap.calculate', $period))->assertRedirect(route('kuota-tangkap.show', $period));

        return $period->refresh();
    }

    /**
     * @param  array<string, string|null>  $overrides  nama perusahaan => ditetapkan
     * @return array<int, array{ditetapkan: string|null, alasan_penyesuaian: string|null}>
     */
    private function allocationPayload(KuotaPeriod $period, array $overrides = [], array $reasons = []): array
    {
        $payload = [];

        foreach ($period->allocations()->with('entitas')->get() as $allocation) {
            $name = $allocation->entitas->name;
            $payload[$allocation->id] = [
                'ditetapkan' => array_key_exists($name, $overrides) ? $overrides[$name] : (string) $allocation->rekomendasi_bulat,
                'alasan_penyesuaian' => $reasons[$name] ?? '',
            ];
        }

        return ['allocations' => $payload];
    }

    public function test_seeder_registers_only_kuota_permissions_and_is_idempotent(): void
    {
        $this->seed(KuotaTangkapPermissionSeeder::class);

        $this->assertSame(
            KuotaTangkapPermissionSeeder::PERMISSIONS,
            Permission::query()->where('name', 'like', 'kuota.%')->orderBy('id')->pluck('name')->all(),
        );
        $this->assertSame(0, Role::query()->count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('kuota-tangkap.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->actingAs($this->makeKuotaUser([]))->get(route('kuota-tangkap.index'))->assertForbidden();
    }

    public function test_index_lists_periods_of_active_year(): void
    {
        $period2026 = $this->makeKuotaPeriod(['kuota_tangkap' => '17000']);
        $this->makeKuotaPeriod(['year' => 2025, 'kuota_tangkap' => '4321']);

        $this->actingAs($this->staff())
            ->get(route('kuota-tangkap.index', ['year' => 2026]))
            ->assertOk()
            ->assertSee('17.000')
            ->assertDontSee('4.321')
            ->assertSee(route('kuota-tangkap.show', $period2026));
    }

    public function test_store_creates_draft_period_and_audit_log(): void
    {
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus salvator', 'trade_status' => 'II']);
        $tradeCode = TradeCode::create(['name' => 'Kulit', 'code' => 'SKI', 'unit' => 'pcs']);
        $provinsi = $this->makeProvinsi();

        $response = $this->actingAs($this->staff())->post(route('kuota-tangkap.store'), [
            'provinsi_id' => (string) $provinsi->id,
            'komoditas_id' => (string) $komoditas->id,
            'species_id' => (string) $species->id,
            'trade_code_id' => (string) $tradeCode->id,
            'year' => '2026',
            'kuota_tangkap' => '17000',
        ]);

        $period = KuotaPeriod::query()->sole();
        $response->assertRedirect(route('kuota-tangkap.show', $period));
        $this->assertSame(KuotaPeriodStatus::Draft, $period->status);
        $this->assertSame('17000.0000', (string) $period->kuota_tangkap);
        $this->assertTrue(KuotaActivity::query()->where('kuota_period_id', $period->id)->where('action', 'created')->exists());
    }

    public function test_store_validation_rejects_missing_fields_duplicates_and_mismatched_species(): void
    {
        $existing = $this->makeKuotaPeriod();
        $otherKomoditas = Komoditas::create(['name' => 'Amfibi', 'code' => 'AMF']);
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('kuota-tangkap.store'), [])
            ->assertSessionHasErrors(['provinsi_id', 'komoditas_id', 'species_id', 'trade_code_id', 'year', 'kuota_tangkap']);

        $this->actingAs($staff)->post(route('kuota-tangkap.store'), [
            ...$existing->only(['provinsi_id', 'komoditas_id', 'species_id', 'trade_code_id', 'year']),
            'kuota_tangkap' => '10',
        ])->assertSessionHasErrors(['species_id' => __('Periode untuk provinsi, jenis, kode perdagangan, dan tahun ini sudah ada.')]);

        $this->actingAs($staff)->post(route('kuota-tangkap.store'), [
            ...$existing->only(['provinsi_id', 'species_id', 'trade_code_id']),
            'komoditas_id' => $otherKomoditas->id,
            'year' => 2027,
            'kuota_tangkap' => '10',
        ])->assertSessionHasErrors(['species_id' => __('Jenis tidak termasuk dalam komoditas yang dipilih.')]);

        $this->assertSame(1, KuotaPeriod::query()->count());
    }

    public function test_deleted_period_does_not_block_recreating_same_combination(): void
    {
        $existing = $this->makeKuotaPeriod();
        $staff = $this->staff();
        $this->actingAs($staff)->delete(route('kuota-tangkap.destroy', $existing))->assertRedirect();

        $this->actingAs($staff)->post(route('kuota-tangkap.store'), [
            ...$existing->only(['provinsi_id', 'komoditas_id', 'species_id', 'trade_code_id', 'year']),
            'kuota_tangkap' => '500',
        ])->assertSessionHasNoErrors()->assertStatus(302)->assertRedirectContains('/kuota-tangkap/');

        $this->assertSame(1, KuotaPeriod::query()->count());
        $this->assertSame(1, KuotaPeriod::withTrashed()->whereNotNull('deleted_at')->count());
    }

    public function test_show_renders_recommendation_table_after_calculation(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);

        $this->actingAs($staff)->get(route('kuota-tangkap.show', $period))
            ->assertOk()
            ->assertSeeInOrder(['PT Ahmad Jaya Sentosa', '2.214', 'PT. Berkat Alam Abadi', '4.242', 'PT Dua Saudara Sakti Berkah', '849', 'PT. Fendibali Leather Indonesia', '5.300', 'CV. Banyuwangi Leather', '2.695'])
            ->assertSee('15.300');
    }

    public function test_adding_participant_after_calculation_resets_status_to_draft(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);
        $baru = $this->makePerusahaan('PT Baru');

        $this->actingAs($staff)->post(route('kuota-tangkap.peserta.store', $period), ['entity_id' => (string) $baru->id, 'permohonan' => '100'])
            ->assertSessionHasNoErrors();

        $this->assertSame(KuotaPeriodStatus::Draft, $period->refresh()->status);
        $this->assertSame('100.0000', (string) $period->allocations()->where('entity_id', $baru->id)->value('permohonan'));
    }

    public function test_participant_must_be_perusahaan_and_unique_per_period(): void
    {
        $staff = $this->staff();
        [$period, $entities] = $this->makeExcelFixturePeriod();
        $penangkaran = Entitas::create(['entity_type' => 'penangkaran', 'name' => 'Penangkaran X']);

        $this->actingAs($staff)->post(route('kuota-tangkap.peserta.store', $period), ['entity_id' => (string) $penangkaran->id])
            ->assertSessionHasErrors('entity_id');
        $this->actingAs($staff)->post(route('kuota-tangkap.peserta.store', $period), ['entity_id' => (string) $entities['PT Ahmad Jaya Sentosa']->id])
            ->assertSessionHasErrors(['entity_id' => __('Perusahaan ini sudah menjadi peserta periode.')]);
    }

    public function test_auto_candidates_add_companies_with_verified_assesment_or_export(): void
    {
        $period = $this->makeKuotaPeriod();
        $dinilai = $this->makePerusahaan('PT Dinilai');
        $eksportir = $this->makePerusahaan('PT Eksportir');
        $this->makePerusahaan('PT Lain');
        $this->giveVerifiedAssesment($dinilai, $period->komoditas_id, '0.5');
        $this->giveRealisasiEkspor($eksportir, $period, '10');

        $this->actingAs($this->staff())->post(route('kuota-tangkap.peserta.candidates', $period))->assertRedirect();

        $this->assertEqualsCanonicalizing([$dinilai->id, $eksportir->id], $period->allocations()->pluck('entity_id')->all());
    }

    public function test_copy_recommendation_fills_only_empty_rows(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);
        $first = $period->allocations()->orderBy('id')->first();
        $first->update(['ditetapkan' => '1000', 'alasan_penyesuaian' => 'Kapasitas']);

        $this->actingAs($staff)->post(route('kuota-tangkap.alokasi.copy', $period))->assertSessionHasNoErrors();

        $this->assertSame('1000.0000', (string) $first->refresh()->ditetapkan);
        $this->assertSame(['1000.0000', '4242.0000', '849.0000', '5300.0000', '2695.0000'], $period->allocations()->orderBy('id')->pluck('ditetapkan')->map(fn ($v) => (string) $v)->all());
    }

    public function test_deviation_without_reason_is_rejected(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);

        $this->actingAs($staff)
            ->put(route('kuota-tangkap.alokasi.update', $period), $this->allocationPayload($period, ['PT Ahmad Jaya Sentosa' => '2000']))
            ->assertSessionHasErrors();

        $this->assertSame(0, $period->allocations()->whereNotNull('ditetapkan')->count());
    }

    public function test_deviation_with_reason_is_saved_and_audited(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);

        $this->actingAs($staff)
            ->put(route('kuota-tangkap.alokasi.update', $period), $this->allocationPayload($period, ['PT Ahmad Jaya Sentosa' => '2000'], ['PT Ahmad Jaya Sentosa' => 'Kapasitas gudang terbatas']))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('kuota_warning');

        $allocation = $period->allocations()->orderBy('id')->first();
        $this->assertSame('2000.0000', (string) $allocation->ditetapkan);
        $this->assertSame('Kapasitas gudang terbatas', $allocation->alasan_penyesuaian);
        $this->assertSame(
            ['ditetapkan' => '2000.0000', 'alasan_penyesuaian' => 'Kapasitas gudang terbatas'],
            KuotaActivity::query()->where('kuota_allocation_id', $allocation->id)->where('action', 'allocated')->sole()->new_values,
        );
    }

    public function test_total_above_quota_is_rejected_and_nothing_changes(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);

        $this->actingAs($staff)
            ->put(route('kuota-tangkap.alokasi.update', $period), $this->allocationPayload($period, ['PT. Fendibali Leather Indonesia' => '7001'], ['PT. Fendibali Leather Indonesia' => 'Tambahan']))
            ->assertSessionHasErrors(['allocations' => __('Total ditetapkan (:total) melebihi kuota ekspor (:kuota).', ['total' => '17.001', 'kuota' => '17.000'])]);

        $this->assertSame(0, $period->allocations()->whereNotNull('ditetapkan')->count());
    }

    public function test_reserve_below_ten_percent_saves_with_warning(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);

        $this->actingAs($staff)
            ->put(route('kuota-tangkap.alokasi.update', $period), $this->allocationPayload($period, ['PT. Fendibali Leather Indonesia' => '7000'], ['PT. Fendibali Leather Indonesia' => 'Tambahan dari cadangan']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('kuota_warning');

        $this->assertSame('7000.0000', (string) $period->allocations()->where('entity_id', $period->allocations()->orderByDesc('rekomendasi_bulat')->value('entity_id'))->value('ditetapkan'));
    }

    public function test_submit_requires_all_rows_filled(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);

        $this->actingAs($staff)->post(route('kuota-tangkap.submit', $period))->assertSessionHasErrors();

        $this->assertSame(KuotaPeriodStatus::Dihitung, $period->refresh()->status);
    }

    public function test_full_workflow_submit_approve_realisasi_close(): void
    {
        Storage::fake('s3');
        $staff = $this->staff();
        $approver = $this->approver();
        $period = $this->calculatedPeriod($staff);
        $this->actingAs($staff)->post(route('kuota-tangkap.alokasi.copy', $period));

        $this->actingAs($staff)->post(route('kuota-tangkap.submit', $period))->assertSessionHasNoErrors();
        $this->assertSame(KuotaPeriodStatus::Diajukan, $period->refresh()->status);
        $this->assertSame($staff->id, $period->submitted_by);

        $this->actingAs($approver)->post(route('kuota-tangkap.approve', $period))->assertSessionHasNoErrors();
        $this->assertSame(KuotaPeriodStatus::Ditetapkan, $period->refresh()->status);
        $this->assertSame($approver->id, $period->approved_by);

        $allocation = $period->allocations()->orderBy('id')->first();
        $this->actingAs($staff)->post(route('kuota-tangkap.realisasi.update', [$period, $allocation]), [
            'realisasi_tangkap' => '1500',
            'sk_tangkap_nomor' => 'SK.12/BBKSDA/2026',
            'sk_tangkap_tanggal' => '2026-03-01',
            'sk_tangkap_file' => UploadedFile::fake()->create('sk.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $allocation->refresh();
        $this->assertSame('1500.0000', (string) $allocation->realisasi_tangkap);
        $this->assertSame('SK.12/BBKSDA/2026', $allocation->sk_tangkap_nomor);
        Storage::disk('s3')->assertExists($allocation->sk_tangkap_path);
        $this->actingAs($staff)->get(route('kuota-tangkap.realisasi.sk', [$period, $allocation]))->assertOk();

        $this->actingAs($staff)->get(route('kuota-tangkap.show', [$period, 'tab' => 'audit']))
            ->assertOk()
            ->assertSee('Nomor SK Kuota Ekspor')
            ->assertDontSee('sk_tangkap_nomor');

        $this->actingAs($approver)->post(route('kuota-tangkap.close', $period))->assertSessionHasNoErrors();
        $this->assertSame(KuotaPeriodStatus::Ditutup, $period->refresh()->status);

        $this->assertSame(
            ['calculated', 'recommendation_copied', 'submitted', 'approved', 'realisasi_updated', 'closed'],
            array_values(array_diff(KuotaActivity::query()->where('kuota_period_id', $period->id)->orderBy('id')->pluck('action')->all(), ['participant_added'])),
        );
    }

    public function test_submitter_cannot_approve_own_submission(): void
    {
        $user = $this->makeKuotaUser([...self::STAFF_PERMISSIONS, 'kuota.approve']);
        $period = $this->calculatedPeriod($user);
        $this->actingAs($user)->post(route('kuota-tangkap.alokasi.copy', $period));
        $this->actingAs($user)->post(route('kuota-tangkap.submit', $period));

        $this->actingAs($user)->post(route('kuota-tangkap.approve', $period))->assertForbidden();

        $this->assertSame(KuotaPeriodStatus::Diajukan, $period->refresh()->status);
    }

    public function test_return_requires_note_and_sends_period_back(): void
    {
        $staff = $this->staff();
        $approver = $this->approver();
        $period = $this->calculatedPeriod($staff);
        $this->actingAs($staff)->post(route('kuota-tangkap.alokasi.copy', $period));
        $this->actingAs($staff)->post(route('kuota-tangkap.submit', $period));

        $this->actingAs($approver)->post(route('kuota-tangkap.return', $period), [])->assertSessionHasErrors('catatan_pengembalian');
        $this->actingAs($approver)->post(route('kuota-tangkap.return', $period), ['catatan_pengembalian' => 'Periksa alokasi Fendibali'])->assertSessionHasNoErrors();

        $period->refresh();
        $this->assertSame(KuotaPeriodStatus::Dihitung, $period->status);
        $this->assertSame('Periksa alokasi Fendibali', $period->catatan_pengembalian);
        $this->assertNull($period->submitted_by);
    }

    public function test_locked_period_rejects_allocation_and_recalculation(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);
        $period->update(['status' => KuotaPeriodStatus::Ditetapkan]);

        $this->actingAs($staff)->put(route('kuota-tangkap.alokasi.update', $period), $this->allocationPayload($period))->assertForbidden();
        $this->actingAs($staff)->post(route('kuota-tangkap.calculate', $period))->assertForbidden();
    }

    public function test_balai_user_cannot_open_period_of_other_province(): void
    {
        $jatim = $this->makeProvinsi('Jawa Timur');
        $bali = $this->makeProvinsi('Bali');
        $balai = Entitas::create(['entity_type' => 'upt_ksda', 'name' => 'BBKSDA Jatim', 'provinsi_id' => $jatim->id]);
        $user = $this->makeKuotaUser(self::STAFF_PERMISSIONS, $balai);
        $periodBali = $this->makeKuotaPeriod(['provinsi_id' => $bali->id]);
        $periodJatim = $this->makeKuotaPeriod(['provinsi_id' => $jatim->id]);

        $this->actingAs($user)->get(route('kuota-tangkap.show', $periodBali))->assertForbidden();
        $this->actingAs($user)->get(route('kuota-tangkap.show', $periodJatim))->assertOk();
        $this->actingAs($user)->get(route('kuota-tangkap.index', ['year' => 2026]))
            ->assertSee(route('kuota-tangkap.show', $periodJatim))
            ->assertDontSee(route('kuota-tangkap.show', $periodBali));
    }

    public function test_allocation_route_is_scoped_to_its_period(): void
    {
        $staff = $this->staff();
        [$periodA] = $this->makeExcelFixturePeriod();
        $periodB = $this->makeKuotaPeriod(['year' => 2027]);
        $foreign = $periodA->allocations()->first();

        $this->actingAs($staff)->delete(route('kuota-tangkap.peserta.destroy', [$periodB, $foreign]))->assertNotFound();
    }

    public function test_company_sees_only_own_allocation_after_approval(): void
    {
        [$period, $entities] = $this->makeExcelFixturePeriod();
        $own = $entities['PT Ahmad Jaya Sentosa'];
        $period->allocations()->where('entity_id', $own->id)->update(['ditetapkan' => '2214']);
        $period->allocations()->where('entity_id', $entities['PT. Berkat Alam Abadi']->id)->update(['ditetapkan' => '4242']);
        $user = $this->makeKuotaUser(['kuota.view-own'], $own);

        $this->actingAs($user)->get(route('kuota-tangkap-saya.index'))->assertOk()->assertDontSee('2.214');

        $period->update(['status' => KuotaPeriodStatus::Ditetapkan]);

        $this->actingAs($user)->get(route('kuota-tangkap-saya.index'))->assertOk()->assertSee('2.214')->assertDontSee('4.242');
        $this->actingAs($user)->get(route('kuota-tangkap.show', $period))->assertForbidden();
    }

    public function test_settings_update_requires_weights_summing_to_hundred_and_keeps_old_snapshots(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);
        $admin = $this->makeKuotaUser(['kuota.settings']);

        $this->actingAs($admin)->put(route('kuota-tangkap.settings.update'), ['bobot_ekspor' => '40', 'bobot_evaluasi' => '50', 'cadangan' => '10'])
            ->assertSessionHasErrors(['bobot_evaluasi' => __('Jumlah bobot ekspor dan bobot evaluasi harus 100%.')]);

        $this->actingAs($admin)->put(route('kuota-tangkap.settings.update'), ['bobot_ekspor' => '40', 'bobot_evaluasi' => '60', 'cadangan' => '5'])
            ->assertSessionHasNoErrors();

        $setting = KuotaSetting::current();
        $this->assertSame(['0.4000', '0.6000', '0.0500'], [(string) $setting->bobot_ekspor, (string) $setting->bobot_evaluasi, (string) $setting->cadangan]);
        $this->assertSame('0.3000', (string) $period->latestSnapshot->bobot_ekspor);
        $this->assertTrue(KuotaActivity::query()->whereNull('kuota_period_id')->where('action', 'settings_updated')->exists());
    }

    public function test_settings_page_requires_settings_permission(): void
    {
        $this->actingAs($this->staff())->get(route('kuota-tangkap.settings.edit'))->assertForbidden();
        $this->actingAs($this->makeKuotaUser(['kuota.settings']))->get(route('kuota-tangkap.settings.edit'))->assertOk()->assertSee('value="30"', false);
    }

    public function test_exports_csv_and_pdf(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);

        $csv = $this->actingAs($staff)->get(route('kuota-tangkap.export.csv', $period));
        $csv->assertOk()->assertDownload('kuota-tangkap-'.$period->id.'-2026.csv');
        $this->assertStringContainsString('"PT Ahmad Jaya Sentosa",5000.0000,120.0000,0.63', $csv->streamedContent());

        $this->actingAs($staff)->get(route('kuota-tangkap.export.pdf', $period))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->approver())->get(route('kuota-tangkap.export.csv', $period))->assertForbidden();
    }

    public function test_snapshot_page_shows_frozen_values(): void
    {
        $staff = $this->staff();
        $period = $this->calculatedPeriod($staff);

        $this->actingAs($staff)->get(route('kuota-tangkap.snapshot', [$period, $period->latestSnapshot]))
            ->assertOk()
            ->assertSee('2.213,9981')
            ->assertSee('15.300');
    }
}
