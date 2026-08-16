<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KomoditasEntitasStatus;
use App\Enums\PermohonanStatus;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\KuotaNasional;
use App\Models\KuotaPerusahaan;
use App\Models\Permohonan;
use App\Models\PurposeOfTransaction;
use App\Models\SourceStatus;
use App\Models\Species;
use App\Models\TradeCode;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PesatsExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeUser(string $entityType, ?string $role = null): User
    {
        $entity = Entitas::create([
            'entity_type' => $entityType,
            'name' => 'Entitas Test',
            'address' => 'Jl. Test',
        ]);

        $user = User::create([
            'name' => 'User Test',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'entity_id' => $entity->id,
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->assignRole($role ?? $entityType);

        return $user;
    }

    private function makeStaffVerifier(): User
    {
        $user = User::create([
            'name' => 'Verifier Test',
            'email' => uniqid('verifier', true).'@example.test',
            'username' => uniqid('verifier', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->givePermissionTo('permohonan.verify');

        return $user;
    }

    private function makeKuotaPerusahaan(Entitas $entitas, float $quota = 100): KuotaPerusahaan
    {
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus salvator', 'trade_status' => 'II']);
        $source = SourceStatus::create(['code' => 'W-'.rand(1000, 9999), 'description' => 'Wild']);
        $tradeCode = TradeCode::create(['name' => 'Kulit', 'code' => 'SKN-'.uniqid(), 'unit' => 'pcs']);

        $kuotaNasional = KuotaNasional::create([
            'komoditas_id' => $komoditas->id,
            'species_id' => $species->id,
            'source_id' => $source->id,
            'trade_code_id' => $tradeCode->id,
            'quota' => $quota + 50,
            'year' => 2026,
        ]);

        KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $entitas->id,
            'file_path' => 'dummy.pdf',
            'tanggal_terbit' => '2026-01-01',
            'tanggal_berakhir' => '2031-01-01',
            'status' => KomoditasEntitasStatus::Verified,
        ]);

        return KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => $quota,
            'year' => 2026,
        ]);
    }

    private function satsLnPayload(KuotaPerusahaan $kuotaPerusahaan, float $qty = 10): array
    {
        $purpose = PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial']);

        return [
            'kuota_perusahaan_id' => (string) $kuotaPerusahaan->id,
            'purpose_of_transaction_id' => (string) $purpose->id,
            'qty' => (string) $qty,
        ];
    }

    public function test_perusahaan_user_can_create_draft_and_add_sats_ln_tab(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity);

        $store = $this->actingAs($user)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();
        $store->assertRedirect(route('pesats-export.edit', $permohonan));

        $this->assertSame(PermohonanStatus::Draft, $permohonan->status);
        $this->assertSame($user->entity_id, $permohonan->entity_id);

        $this->actingAs($user)
            ->post(route('pesats-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10))
            ->assertRedirect(route('pesats-export.edit', $permohonan));

        $this->assertSame(1, $permohonan->satsLn()->count());
        $item = $permohonan->satsLn()->first()->items->first();
        $this->assertSame('10.00', $item->qty);
        $this->assertSame($kuotaPerusahaan->kuotaNasional->species_id, $item->species_id);
    }

    public function test_cannot_add_sats_ln_exceeding_sisa_kuota_perusahaan(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 20);

        $this->actingAs($user)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();

        $response = $this->actingAs($user)->post(
            route('pesats-export.sats-ln.store', $permohonan),
            $this->satsLnPayload($kuotaPerusahaan, 30)
        );

        $response->assertSessionHasErrors('qty');
        $this->assertSame(0, $permohonan->satsLn()->count());
    }

    public function test_full_flow_from_submit_to_siap_diambil(): void
    {
        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->actingAs($user)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('pesats-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));

        $this->actingAs($user)->post(route('pesats-export.submit', $permohonan))
            ->assertRedirect(route('pesats-export.index'));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::AntrianVerifikasi, $permohonan->status);
        $this->assertNotNull($permohonan->submitted_at);

        $this->actingAs($verifier)->post(route('pesats-export.verifikasi.verify', $permohonan))
            ->assertRedirect(route('pesats-export.verifikasi.index'));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::DalamProsesVerifikasi, $permohonan->status);
        $this->assertSame($verifier->id, $permohonan->verified_by);

        $satsLn = $permohonan->satsLn()->firstOrFail();

        $this->actingAs($verifier)->post(route('pesats-export.verifikasi.mark-printed', [$permohonan, $satsLn]))
            ->assertRedirect(route('pesats-export.verifikasi.show', $permohonan));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::DicetakSebagian, $permohonan->status);

        $this->actingAs($verifier)->post(route('pesats-export.verifikasi.mark-signed', [$permohonan, $satsLn]))
            ->assertRedirect(route('pesats-export.verifikasi.show', $permohonan));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::SiapDiambil, $permohonan->status);
    }

    public function test_reject_sends_permohonan_back_to_ditolak_and_entity_can_edit_again(): void
    {
        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->actingAs($user)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('pesats-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('pesats-export.submit', $permohonan));

        $this->actingAs($verifier)->post(route('pesats-export.verifikasi.reject', $permohonan), [
            'catatan_verifikasi' => 'Dokumen kurang lengkap.',
        ])->assertRedirect(route('pesats-export.verifikasi.index'));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::Ditolak, $permohonan->status);
        $this->assertSame('Dokumen kurang lengkap.', $permohonan->catatan_verifikasi);

        $this->actingAs($user)->get(route('pesats-export.edit', $permohonan))->assertOk();
    }

    public function test_stranger_cannot_access_another_entitys_permohonan(): void
    {
        $owner = $this->makeUser('perusahaan');
        $stranger = $this->makeUser('perusahaan');

        $this->actingAs($owner)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($stranger)->get(route('pesats-export.edit', $permohonan))->assertForbidden();
    }

    public function test_index_and_verifikasi_views_render(): void
    {
        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->actingAs($user)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('pesats-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('pesats-export.submit', $permohonan));

        $this->actingAs($user)->get(route('pesats-export.index'))->assertOk();
        $this->actingAs($verifier)->get(route('pesats-export.verifikasi.index'))->assertOk();
        $this->actingAs($verifier)->get(route('pesats-export.verifikasi.show', $permohonan))->assertOk();

        $this->actingAs($verifier)->post(route('pesats-export.verifikasi.verify', $permohonan));
        $this->actingAs($user)->get(route('pesats-export.show', $permohonan))->assertOk();
    }

    public function test_cannot_exceed_max_25_sats_ln_per_permohonan(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 1000);

        $this->actingAs($user)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();

        for ($i = 0; $i < 25; $i++) {
            $this->actingAs($user)->post(route('pesats-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 1));
        }

        $this->assertSame(25, $permohonan->satsLn()->count());

        $this->actingAs($user)->post(route('pesats-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 1))
            ->assertRedirect(route('pesats-export.edit', $permohonan));

        $this->assertSame(25, $permohonan->satsLn()->count());
    }

    public function test_submit_dispatches_whatsapp_notification_to_verifiers_with_a_phone_number(): void
    {
        Bus::fake([SendWhatsAppNotificationJob::class]);

        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $verifier->update(['phone_number' => '15551234567']);
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->actingAs($user)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('pesats-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('pesats-export.submit', $permohonan));

        Bus::assertDispatched(SendWhatsAppNotificationJob::class, fn ($job) => $job->to === '15551234567');
    }

    public function test_verify_dispatches_whatsapp_notification_to_submitter_with_a_phone_number(): void
    {
        Bus::fake([SendWhatsAppNotificationJob::class]);

        $user = $this->makeUser('perusahaan');
        $user->update(['phone_number' => '15557654321']);
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->actingAs($user)->post(route('pesats-export.store'));
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('pesats-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('pesats-export.submit', $permohonan));

        $this->actingAs($verifier)->post(route('pesats-export.verifikasi.verify', $permohonan));

        Bus::assertDispatched(SendWhatsAppNotificationJob::class, fn ($job) => $job->to === '15557654321');
    }
}
