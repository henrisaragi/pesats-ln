<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\JenisPermohonan;
use App\Enums\KomoditasEntitasStatus;
use App\Enums\PermohonanStatus;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Country;
use App\Models\Customer\Customer;
use App\Models\Document;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\KuotaNasional;
use App\Models\KuotaPerusahaan;
use App\Models\Permohonan;
use App\Models\Port;
use App\Models\PurposeOfTransaction;
use App\Models\SatsLn;
use App\Models\SourceStatus;
use App\Models\Species;
use App\Models\TradeCode;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PermohonanExportTest extends TestCase
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

        $user->givePermissionTo('permohonan-export.verify');

        return $user;
    }

    private function createPermohonanDocument(User $user): Document
    {
        return Document::create([
            'entity_id' => $user->entity_id,
            'created_by_id' => $user->id,
            'nomor_document' => 'DOC-'.uniqid(),
            'type_document' => DocumentType::Permohonan,
            'issued_date' => '2026-01-10',
            'issued_country_id' => Country::query()->firstOrCreate(
                ['iso_code' => 'ID'],
                ['name' => 'Indonesia', 'iso_code3' => 'IDN', 'phone_code' => '62']
            )->id,
            'path' => 'documents/dummy.pdf',
        ]);
    }

    private function createPermohonan(User $user): TestResponse
    {
        $document = $this->createPermohonanDocument($user);

        return $this->actingAs($user)->post(route('permohonan-export.store'), [
            'document_id' => (string) $document->id,
            'jenis_permohonan' => JenisPermohonan::Baru->value,
        ]);
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

        $document = $this->createPermohonanDocument($user);
        $store = $this->actingAs($user)->post(route('permohonan-export.store'), [
            'document_id' => (string) $document->id,
            'jenis_permohonan' => JenisPermohonan::Baru->value,
        ]);
        $permohonan = Permohonan::firstOrFail();
        $store->assertRedirect(route('permohonan-export.show', $permohonan));

        $this->assertSame(JenisPermohonan::Baru, $permohonan->jenis_permohonan);
        $this->assertSame($document->id, $permohonan->document?->id);

        $this->assertSame(PermohonanStatus::Draft, $permohonan->status);
        $this->assertSame($user->entity_id, $permohonan->entity_id);

        $this->actingAs($user)->get(route('permohonan-export.show', $permohonan))->assertOk();

        $this->actingAs($user)
            ->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10))
            ->assertRedirect(route('permohonan-export.edit', $permohonan));

        $this->assertSame(1, $permohonan->satsLn()->count());
        $item = $permohonan->satsLn()->first()->items->first();
        $this->assertSame('10.00', $item->qty);
        $this->assertSame($kuotaPerusahaan->kuotaNasional->species_id, $item->species_id);
    }

    public function test_cannot_add_sats_ln_exceeding_sisa_kuota_perusahaan(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 20);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $response = $this->actingAs($user)->post(
            route('permohonan-export.sats-ln.store', $permohonan),
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

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));

        $this->actingAs($user)->post(route('permohonan-export.submit', $permohonan))
            ->assertRedirect(route('permohonan-export.index'));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::AntrianVerifikasi, $permohonan->status);
        $this->assertNotNull($permohonan->submitted_at);

        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.verify', $permohonan))
            ->assertRedirect(route('permohonan-export.verifikasi.index'));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::DalamProsesVerifikasi, $permohonan->status);
        $this->assertSame($verifier->id, $permohonan->verified_by);

        $satsLn = $permohonan->satsLn()->firstOrFail();

        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.mark-printed', [$permohonan, $satsLn]))
            ->assertRedirect(route('permohonan-export.verifikasi.show', $permohonan));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::DicetakSebagian, $permohonan->status);

        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.mark-signed', [$permohonan, $satsLn]))
            ->assertRedirect(route('permohonan-export.verifikasi.show', $permohonan));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::SiapDiambil, $permohonan->status);
    }

    public function test_reject_sends_permohonan_back_to_ditolak_and_entity_can_edit_again(): void
    {
        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('permohonan-export.submit', $permohonan));

        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.reject', $permohonan), [
            'catatan_verifikasi' => 'Dokumen kurang lengkap.',
        ])->assertRedirect(route('permohonan-export.verifikasi.index'));

        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::Ditolak, $permohonan->status);
        $this->assertSame('Dokumen kurang lengkap.', $permohonan->catatan_verifikasi);

        $this->actingAs($user)->get(route('permohonan-export.edit', $permohonan))->assertOk();
    }

    public function test_stranger_cannot_access_another_entitys_permohonan(): void
    {
        $owner = $this->makeUser('perusahaan');
        $stranger = $this->makeUser('perusahaan');

        $this->createPermohonan($owner);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($stranger)->get(route('permohonan-export.edit', $permohonan))->assertForbidden();
    }

    public function test_index_and_verifikasi_views_render(): void
    {
        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('permohonan-export.submit', $permohonan));

        $this->actingAs($user)->get(route('permohonan-export.index'))->assertOk();
        $this->actingAs($verifier)->get(route('permohonan-export.verifikasi.index'))->assertOk();
        $this->actingAs($verifier)->get(route('permohonan-export.verifikasi.show', $permohonan))->assertOk();

        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.verify', $permohonan));
        $this->actingAs($user)->get(route('permohonan-export.show', $permohonan))->assertOk();
    }

    public function test_cannot_exceed_max_25_sats_ln_per_permohonan(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 1000);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        for ($i = 0; $i < 25; $i++) {
            $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 1));
        }

        $this->assertSame(25, $permohonan->satsLn()->count());

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 1))
            ->assertRedirect(route('permohonan-export.edit', $permohonan));

        $this->assertSame(25, $permohonan->satsLn()->count());
    }

    public function test_submit_dispatches_whatsapp_notification_to_verifiers_with_a_phone_number(): void
    {
        Bus::fake([SendWhatsAppNotificationJob::class]);

        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $verifier->update(['phone_number' => '15551234567']);
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('permohonan-export.submit', $permohonan));

        Bus::assertDispatched(SendWhatsAppNotificationJob::class, fn ($job) => $job->to === '15551234567');
    }

    public function test_verify_dispatches_whatsapp_notification_to_submitter_with_a_phone_number(): void
    {
        Bus::fake([SendWhatsAppNotificationJob::class]);

        $user = $this->makeUser('perusahaan');
        $user->update(['phone_number' => '15557654321']);
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('permohonan-export.submit', $permohonan));

        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.verify', $permohonan));

        Bus::assertDispatched(SendWhatsAppNotificationJob::class, fn ($job) => $job->to === '15557654321');
    }

    public function test_store_rejects_document_belonging_to_another_entity(): void
    {
        $user = $this->makeUser('perusahaan');
        $stranger = $this->makeUser('perusahaan');
        $document = $this->createPermohonanDocument($stranger);

        $this->actingAs($user)->post(route('permohonan-export.store'), [
            'document_id' => (string) $document->id,
            'jenis_permohonan' => JenisPermohonan::Baru->value,
        ])->assertSessionHasErrors('document_id');

        $this->assertSame(0, Permohonan::query()->count());
    }

    public function test_store_rejects_document_already_linked_to_another_permohonan(): void
    {
        $user = $this->makeUser('perusahaan');
        $document = $this->createPermohonanDocument($user);

        $this->actingAs($user)->post(route('permohonan-export.store'), [
            'document_id' => (string) $document->id,
            'jenis_permohonan' => JenisPermohonan::Baru->value,
        ]);
        $this->assertSame(1, Permohonan::query()->count());

        $this->actingAs($user)->post(route('permohonan-export.store'), [
            'document_id' => (string) $document->id,
            'jenis_permohonan' => JenisPermohonan::Perubahan->value,
        ])->assertSessionHasErrors('document_id');

        $this->assertSame(1, Permohonan::query()->count());
    }

    public function test_sats_ln_can_be_created_without_kuota_and_qty(): void
    {
        $user = $this->makeUser('perusahaan');
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $indonesia = Country::query()->firstOrCreate(['iso_code' => 'ID'], ['name' => 'Indonesia', 'iso_code3' => 'IDN', 'phone_code' => '62']);
        $singapore = Country::create(['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code3' => 'SGP', 'phone_code' => '65']);
        $customer = Customer::create(['name' => 'Buyer Co', 'country_id' => $singapore->id, 'address' => 'Jl. Buyer', 'entity_id' => $user->entity_id]);
        $purpose = PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial']);
        $portExport = Port::create(['country_id' => $indonesia->id, 'code' => 'IDTPP', 'name' => 'Tanjung Priok', 'type' => 'sea']);
        $portDestination = Port::create(['country_id' => $singapore->id, 'code' => 'SGSIN', 'name' => 'Singapore Port', 'type' => 'sea']);

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), [
            'customer_company_id' => (string) $customer->id,
            'purpose_of_transaction_id' => (string) $purpose->id,
            'country_of_exportation' => (string) $indonesia->id,
            'country_of_destination' => (string) $singapore->id,
            'port_of_exportation' => (string) $portExport->id,
            'port_of_destination' => (string) $portDestination->id,
            'return_to' => 'show',
        ]);

        $this->assertSame(1, $permohonan->satsLn()->count());
        $satsLn = $permohonan->satsLn()->firstOrFail();
        $response->assertRedirect(route('permohonan-export.sats-ln.show', [$permohonan, $satsLn]));
        $this->assertSame($customer->id, $satsLn->customer_company_id);
        $this->assertNull($satsLn->items()->first());
    }

    public function test_sats_ln_store_defaults_to_edit_redirect_when_return_to_is_not_sent(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity);
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)
            ->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10))
            ->assertRedirect(route('permohonan-export.edit', $permohonan));
    }

    public function test_port_options_only_returns_ports_for_the_given_country(): void
    {
        $user = $this->makeUser('perusahaan');
        $indonesia = Country::query()->firstOrCreate(['iso_code' => 'ID'], ['name' => 'Indonesia', 'iso_code3' => 'IDN', 'phone_code' => '62']);
        $singapore = Country::create(['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code3' => 'SGP', 'phone_code' => '65']);
        Port::create(['country_id' => $indonesia->id, 'code' => 'IDTPP', 'name' => 'Tanjung Priok', 'type' => 'sea']);
        $singaporePort = Port::create(['country_id' => $singapore->id, 'code' => 'SGSIN', 'name' => 'Singapore Port', 'type' => 'sea']);

        $response = $this->actingAs($user)->getJson(route('permohonan-export.port-options', $singapore));

        $response->assertOk();
        $response->assertJson([['id' => $singaporePort->id, 'name' => 'Singapore Port']]);
        $this->assertCount(1, $response->json());
    }

    public function test_show_page_renders_with_a_sats_ln_present(): void
    {
        $user = $this->makeUser('perusahaan');
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $indonesia = Country::query()->firstOrCreate(['iso_code' => 'ID'], ['name' => 'Indonesia', 'iso_code3' => 'IDN', 'phone_code' => '62']);
        $singapore = Country::create(['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code3' => 'SGP', 'phone_code' => '65']);
        $customer = Customer::create(['name' => 'Buyer Co', 'country_id' => $singapore->id, 'address' => 'Jl. Buyer', 'entity_id' => $user->entity_id]);
        $purpose = PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial']);
        $portExport = Port::create(['country_id' => $indonesia->id, 'code' => 'IDTPP', 'name' => 'Tanjung Priok', 'type' => 'sea']);
        $portDestination = Port::create(['country_id' => $singapore->id, 'code' => 'SGSIN', 'name' => 'Singapore Port', 'type' => 'sea']);

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), [
            'customer_company_id' => (string) $customer->id,
            'purpose_of_transaction_id' => (string) $purpose->id,
            'country_of_exportation' => (string) $indonesia->id,
            'country_of_destination' => (string) $singapore->id,
            'port_of_exportation' => (string) $portExport->id,
            'port_of_destination' => (string) $portDestination->id,
            'return_to' => 'show',
        ]);

        $this->actingAs($user)->get(route('permohonan-export.show', $permohonan))->assertOk();
    }

    public function test_show_page_has_a_quick_add_customer_trigger_wired_to_the_satsln_customer_select(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $satsLn = $permohonan->satsLn()->firstOrFail();

        $response = $this->actingAs($user)->get(route('permohonan-export.sats-ln.show', [$permohonan, $satsLn]));

        $response->assertOk();
        $response->assertSee('id="customer-quick-create-modal"', false);
        $response->assertSee('data-hs-overlay="#customer-quick-create-modal"', false);
        $response->assertSee('data-target-select="satsln-modal-'.$satsLn->id.'-customer"', false);
        $response->assertSee('data-return-modal="satsln-edit-modal-'.$satsLn->id.'"', false);
    }

    public function test_sats_ln_detail_page_renders_for_a_specific_sats_ln(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 5));

        $satsLnIds = $permohonan->satsLn()->orderBy('id')->pluck('id');
        $this->assertSame(2, $satsLnIds->count());

        $firstSatsLn = SatsLn::findOrFail($satsLnIds->first());

        $this->actingAs($user)
            ->get(route('permohonan-export.sats-ln.show', [$permohonan, $firstSatsLn]))
            ->assertOk();
    }

    public function test_sats_ln_detail_page_404s_for_sats_ln_belonging_to_another_permohonan(): void
    {
        $user = $this->makeUser('perusahaan');
        $this->createPermohonan($user);
        $firstPermohonan = Permohonan::firstOrFail();

        $document = $this->createPermohonanDocument($user);
        $this->actingAs($user)->post(route('permohonan-export.store'), [
            'document_id' => (string) $document->id,
            'jenis_permohonan' => JenisPermohonan::Baru->value,
        ]);
        $secondPermohonan = Permohonan::query()->where('id', '!=', $firstPermohonan->id)->firstOrFail();

        $indonesia = Country::query()->firstOrCreate(['iso_code' => 'ID'], ['name' => 'Indonesia', 'iso_code3' => 'IDN', 'phone_code' => '62']);
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $secondPermohonan), [
            'customer_company_id' => null,
            'purpose_of_transaction_id' => PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial'])->id,
            'country_of_exportation' => $indonesia->id,
        ]);
        $satsLn = $secondPermohonan->satsLn()->firstOrFail();

        $this->actingAs($user)
            ->get(route('permohonan-export.sats-ln.show', [$firstPermohonan, $satsLn]))
            ->assertNotFound();
    }

    public function test_sats_ln_destroy_defaults_to_edit_redirect_when_return_to_is_not_sent(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity);
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $satsLn = $permohonan->satsLn()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('permohonan-export.sats-ln.destroy', [$permohonan, $satsLn]))
            ->assertRedirect(route('permohonan-export.edit', $permohonan));

        $this->assertSame(0, $permohonan->satsLn()->count());
    }

    public function test_sats_ln_destroy_redirects_to_show_when_return_to_is_show(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity);
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $satsLn = $permohonan->satsLn()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('permohonan-export.sats-ln.destroy', [$permohonan, $satsLn]), ['return_to' => 'show'])
            ->assertRedirect(route('permohonan-export.show', $permohonan));

        $this->assertSame(0, $permohonan->satsLn()->count());
    }

    private function makeSatsLnForItems(User $user): SatsLn
    {
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $indonesia = Country::query()->firstOrCreate(['iso_code' => 'ID'], ['name' => 'Indonesia', 'iso_code3' => 'IDN', 'phone_code' => '62']);
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), [
            'customer_company_id' => null,
            'purpose_of_transaction_id' => PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial'])->id,
            'country_of_exportation' => $indonesia->id,
        ]);

        return $permohonan->satsLn()->firstOrFail();
    }

    public function test_sats_ln_item_can_be_added_with_valid_combination(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $satsLn = $this->makeSatsLnForItems($user);
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '10',
        ]);

        $response->assertRedirect(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]));
        $this->assertSame('Spesies berhasil ditambahkan.', session('toast_success'));
        $this->assertSame(1, $satsLn->items()->count());
        $item = $satsLn->items()->firstOrFail();
        $this->assertSame($kuotaNasional->species_id, $item->species_id);
        $this->assertSame($kuotaPerusahaan->id, $item->kuota_perusahaan_id);
        $this->assertSame('10.00', $item->qty);
    }

    public function test_sats_ln_item_fails_when_combination_not_in_kuota_perusahaan(): void
    {
        $user = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);

        // Kombinasi acak yang tidak dipunyai entitas ini di kuota_perusahaan.
        $komoditas = Komoditas::create(['name' => 'Reptil Lain', 'code' => 'RPT2-'.uniqid()]);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus komodoensis', 'trade_status' => 'I']);
        $source = SourceStatus::create(['code' => 'W2-'.rand(1000, 9999), 'description' => 'Wild 2']);
        $tradeCode = TradeCode::create(['name' => 'Kulit Lain', 'code' => 'SKN2-'.uniqid(), 'unit' => 'pcs']);

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $source->id,
            'komoditas_id' => (string) $komoditas->id,
            'trade_code_id' => (string) $tradeCode->id,
            'species_id' => (string) $species->id,
            'qty' => '5',
        ]);

        $response->assertSessionHasErrors('species_id');
        $this->assertSame(0, $satsLn->items()->count());
    }

    public function test_sats_ln_item_fails_when_qty_exceeds_sisa_kuota(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 10);
        $satsLn = $this->makeSatsLnForItems($user);
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '999',
        ]);

        $response->assertSessionHasErrors('qty');
        $this->assertSame(0, $satsLn->items()->count());
    }

    public function test_sats_ln_item_fails_when_komoditas_differs_from_existing_item(): void
    {
        $user = $this->makeUser('perusahaan');
        $firstKuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $secondKuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $satsLn = $this->makeSatsLnForItems($user);

        $firstKuotaNasional = $firstKuotaPerusahaan->kuotaNasional;
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $firstKuotaNasional->source_id,
            'komoditas_id' => (string) $firstKuotaNasional->komoditas_id,
            'trade_code_id' => (string) $firstKuotaNasional->trade_code_id,
            'species_id' => (string) $firstKuotaNasional->species_id,
            'qty' => '10',
        ])->assertRedirect();
        $this->assertSame(1, $satsLn->items()->count());

        $secondKuotaNasional = $secondKuotaPerusahaan->kuotaNasional;
        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $secondKuotaNasional->source_id,
            'komoditas_id' => (string) $secondKuotaNasional->komoditas_id,
            'trade_code_id' => (string) $secondKuotaNasional->trade_code_id,
            'species_id' => (string) $secondKuotaNasional->species_id,
            'qty' => '5',
        ]);

        $response->assertSessionHasErrors('komoditas_id');
        $this->assertSame(1, $satsLn->items()->count());
    }

    public function test_item_options_only_return_combinations_owned_by_the_authenticated_entitas(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;

        $stranger = $this->makeUser('perusahaan');
        $this->makeKuotaPerusahaan($stranger->entity, 100);

        $komoditasResponse = $this->actingAs($user)
            ->getJson(route('permohonan-export.item-komoditas-options', ['source' => $kuotaNasional->source_id]));
        $komoditasResponse->assertOk();
        $komoditasIds = collect($komoditasResponse->json())->pluck('id');
        $this->assertTrue($komoditasIds->contains($kuotaNasional->komoditas_id));
        $this->assertSame(1, $komoditasIds->count());

        $tradeCodeResponse = $this->actingAs($user)->getJson(route('permohonan-export.item-trade-code-options', [
            'source' => $kuotaNasional->source_id,
            'komoditas' => $kuotaNasional->komoditas_id,
        ]));
        $tradeCodeResponse->assertOk();
        $this->assertSame([$kuotaNasional->trade_code_id], collect($tradeCodeResponse->json())->pluck('id')->all());

        $speciesResponse = $this->actingAs($user)->getJson(route('permohonan-export.item-species-options', [
            'source' => $kuotaNasional->source_id,
            'komoditas' => $kuotaNasional->komoditas_id,
            'tradeCode' => $kuotaNasional->trade_code_id,
        ]));
        $speciesResponse->assertOk();
        $this->assertSame([$kuotaNasional->species_id], collect($speciesResponse->json())->pluck('id')->all());
    }

    public function test_sats_ln_item_can_be_updated(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $satsLn = $this->makeSatsLnForItems($user);
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '10',
        ]);
        $item = $satsLn->items()->firstOrFail();

        $response = $this->actingAs($user)->put(route('permohonan-export.sats-ln.items.update', [$satsLn->permohonan, $satsLn, $item]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '20',
        ]);

        $response->assertRedirect(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]));
        $this->assertSame('Spesies berhasil diperbarui.', session('toast_success'));
        $item->refresh();
        $this->assertSame('20.00', $item->qty);
    }

    public function test_sats_ln_item_update_excludes_its_own_qty_from_sisa_kuota_check(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $satsLn = $this->makeSatsLnForItems($user);
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '90',
        ]);
        $item = $satsLn->items()->firstOrFail();

        // Sisa kuota kalau item ini dihitung sebagai "sudah terpakai" cuma 10 (100-90) - tapi
        // karena ini EDIT terhadap item itu sendiri, qty lama (90) harus dikecualikan dulu, jadi
        // menaikkan qty ke 95 (< kuota total 100) harus tetap berhasil.
        $response = $this->actingAs($user)->put(route('permohonan-export.sats-ln.items.update', [$satsLn->permohonan, $satsLn, $item]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '95',
        ]);

        $response->assertRedirect();
        $item->refresh();
        $this->assertSame('95.00', $item->qty);
    }

    public function test_sats_ln_item_update_fails_when_new_qty_exceeds_kuota_even_excluding_self(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $satsLn = $this->makeSatsLnForItems($user);
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '10',
        ]);
        $item = $satsLn->items()->firstOrFail();

        $response = $this->actingAs($user)->put(route('permohonan-export.sats-ln.items.update', [$satsLn->permohonan, $satsLn, $item]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '150',
        ]);

        $response->assertSessionHasErrors('qty');
        $item->refresh();
        $this->assertSame('10.00', $item->qty);
    }

    public function test_sats_ln_item_update_fails_when_komoditas_differs_from_other_items(): void
    {
        $user = $this->makeUser('perusahaan');
        $firstKuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $secondKuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $satsLn = $this->makeSatsLnForItems($user);
        $firstKuotaNasional = $firstKuotaPerusahaan->kuotaNasional;

        // Dua item dengan komoditas yang sama (sah) - lalu coba ubah item PERTAMA ke komoditas
        // yang beda, yang sekarang akan konflik dengan item KEDUA yang masih ada.
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $firstKuotaNasional->source_id,
            'komoditas_id' => (string) $firstKuotaNasional->komoditas_id,
            'trade_code_id' => (string) $firstKuotaNasional->trade_code_id,
            'species_id' => (string) $firstKuotaNasional->species_id,
            'qty' => '10',
        ]);
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $firstKuotaNasional->source_id,
            'komoditas_id' => (string) $firstKuotaNasional->komoditas_id,
            'trade_code_id' => (string) $firstKuotaNasional->trade_code_id,
            'species_id' => (string) $firstKuotaNasional->species_id,
            'qty' => '5',
        ]);
        $this->assertSame(2, $satsLn->items()->count());
        $firstItem = $satsLn->items()->orderBy('id')->firstOrFail();

        $response = $this->actingAs($user)->put(route('permohonan-export.sats-ln.items.update', [$satsLn->permohonan, $satsLn, $firstItem]), [
            'source_id' => (string) $secondKuotaPerusahaan->kuotaNasional->source_id,
            'komoditas_id' => (string) $secondKuotaPerusahaan->kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $secondKuotaPerusahaan->kuotaNasional->trade_code_id,
            'species_id' => (string) $secondKuotaPerusahaan->kuotaNasional->species_id,
            'qty' => '5',
        ]);

        $response->assertSessionHasErrors('komoditas_id');
        $firstItem->refresh();
        $this->assertSame((int) $firstKuotaNasional->komoditas_id, (int) $firstItem->kuotaNasional->komoditas_id);
    }
}
