<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\JenisPermohonan;
use App\Enums\KomoditasEntitasStatus;
use App\Enums\NotificationCategory;
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
use App\Notifications\AppNotification;
use App\Services\PermohonanService;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
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
        return $this->makeStaffUser([
            'permohonan-export.verify',
            'permohonan.verify.view',
            'permohonan.verify-detail',
        ]);
    }

    /**
     * @param  string|array<int, string>  $permissions
     */
    private function makeStaffUser(string|array $permissions): User
    {
        $user = User::create([
            'name' => 'Verifier Test',
            'email' => uniqid('verifier', true).'@example.test',
            'username' => uniqid('verifier', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->givePermissionTo($permissions);

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

    private function createLampiranDocument(User $user, DocumentType $type = DocumentType::SertifikatStandar): Document
    {
        return Document::create([
            'entity_id' => $user->entity_id,
            'created_by_id' => $user->id,
            'nomor_document' => 'DOC-'.uniqid(),
            'type_document' => $type,
            'issued_date' => '2026-01-10',
            'issued_country_id' => Country::query()->firstOrCreate(
                ['iso_code' => 'ID'],
                ['name' => 'Indonesia', 'iso_code3' => 'IDN', 'phone_code' => '62']
            )->id,
            'path' => 'documents/lampiran-'.uniqid().'.pdf',
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

    private function makeKuotaPerusahaan(Entitas $entitas, float $quota = 100, string $komoditasName = 'Reptil'): KuotaPerusahaan
    {
        $komoditas = Komoditas::create(['name' => $komoditasName, 'code' => 'RPT-'.uniqid()]);
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

    /**
     * Kuota perusahaan baru untuk entitas LAIN, menunjuk ke kuota_nasional yang SUDAH ADA -
     * dipakai untuk menguji realisasi kuota nasional yang terakumulasi lintas entitas (Revisi
     * Permohonan 4).
     */
    private function makeKuotaPerusahaanForNasional(Entitas $entitas, KuotaNasional $kuotaNasional, float $quota): KuotaPerusahaan
    {
        KomoditasEntitas::create([
            'komoditas_id' => $kuotaNasional->komoditas_id,
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

    /**
     * Payload minimal untuk store SatsLn tanpa kuota/spesies (cuma header) - purpose_of_transaction_id
     * satu-satunya field yang wajib diisi di SatsLnRequest.
     */
    private function minimalSatsLnPayload(array $overrides = []): array
    {
        $purpose = PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial']);

        return array_merge(['purpose_of_transaction_id' => (string) $purpose->id], $overrides);
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

    public function test_verifikasi_show_page_renders_selected_sats_ln_and_gates_action_buttons_by_permission(): void
    {
        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 5));

        $satsLn = $permohonan->satsLn()->orderBy('id')->firstOrFail();

        // makeStaffVerifier() only grants permohonan-export.verify (plus permohonan.verify-detail
        // for page access), so only the "Verifikasi" action button should render - the other 5
        // are gated behind their own permission.
        $this->actingAs($verifier)
            ->get(route('permohonan-export.verifikasi.sats-ln.show', [$permohonan, $satsLn]))
            ->assertOk()
            ->assertSee(__('Verifikasi'))
            ->assertDontSee(__('Disposisi'))
            ->assertDontSee(__('Print/Cetak'))
            ->assertDontSee(__('Siap Diambil'))
            ->assertDontSee(__('Telah Diambil'));

        $verifier->givePermissionTo([
            'permohonan-export.disposisi',
            'permohonan-export.print',
            'permohonan-export.update-status',
            'permohonan-export.siap-diambil',
            'permohonan-export.telah-diambil',
        ]);

        $this->actingAs($verifier)
            ->get(route('permohonan-export.verifikasi.sats-ln.show', [$permohonan, $satsLn]))
            ->assertOk()
            ->assertSee(__('Disposisi'))
            ->assertSee(__('Print/Cetak'))
            ->assertSee(__('Siap Diambil'))
            ->assertSee(__('Telah Diambil'));
    }

    public function test_verifikasi_sats_ln_show_404s_for_sats_ln_belonging_to_another_permohonan(): void
    {
        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();

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

        $this->actingAs($verifier)
            ->get(route('permohonan-export.verifikasi.sats-ln.show', [$firstPermohonan, $satsLn]))
            ->assertNotFound();
    }

    public function test_verifikasi_index_detail_and_verify_action_are_gated_by_independent_permissions(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $satsLn = $permohonan->satsLn()->firstOrFail();

        // Has permohonan-export.verify (Verifikasi action) only - the antrian list and the
        // detail pages need their own dedicated permission and must stay out of reach.
        $verifyActionOnly = $this->makeStaffUser('permohonan-export.verify');

        $this->actingAs($verifyActionOnly)->get(route('permohonan-export.verifikasi.index'))->assertForbidden();
        $this->actingAs($verifyActionOnly)->get(route('permohonan-export.verifikasi.show', $permohonan))->assertForbidden();
        $this->actingAs($verifyActionOnly)
            ->get(route('permohonan-export.verifikasi.sats-ln.show', [$permohonan, $satsLn]))
            ->assertForbidden();

        // permohonan.verify.view alone opens the antrian list but not the detail pages.
        $listOnly = $this->makeStaffUser('permohonan.verify.view');

        $this->actingAs($listOnly)->get(route('permohonan-export.verifikasi.index'))->assertOk();
        $this->actingAs($listOnly)->get(route('permohonan-export.verifikasi.show', $permohonan))->assertForbidden();

        // permohonan.verify-detail alone opens the detail pages but not the antrian list.
        $detailOnly = $this->makeStaffUser('permohonan.verify-detail');

        $this->actingAs($detailOnly)->get(route('permohonan-export.verifikasi.index'))->assertForbidden();
        $this->actingAs($detailOnly)->get(route('permohonan-export.verifikasi.show', $permohonan))->assertOk();
        $this->actingAs($detailOnly)
            ->get(route('permohonan-export.verifikasi.sats-ln.show', [$permohonan, $satsLn]))
            ->assertOk();
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

    public function test_submit_sats_ln_succeeds_when_all_sats_ln_have_items_and_documents(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $satsLn = $permohonan->satsLn()->firstOrFail();

        $document = $this->createLampiranDocument($user);
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.documents.attach', [$permohonan, $satsLn]), [
            'document_id' => (string) $document->id,
        ]);

        $this->actingAs($user)->get(route('permohonan-export.show', $permohonan))
            ->assertSee('action="'.route('permohonan-export.sats-ln.submit', $permohonan).'"', false);

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.submit', $permohonan));

        $response->assertRedirect(route('permohonan-export.show', $permohonan));
        $this->assertSame('SATS-LN berhasil diajukan.', session('success'));
        $permohonan->refresh();
        $this->assertSame(PermohonanStatus::Submitted, $permohonan->status);
        $this->assertNotNull($permohonan->submitted_at);
    }

    public function test_submit_sats_ln_fails_when_permohonan_has_no_sats_ln(): void
    {
        $user = $this->makeUser('perusahaan');
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.submit', $permohonan));

        $response->assertRedirect(route('permohonan-export.show', $permohonan));
        $this->assertNotNull(session('error'));
        $this->assertSame(PermohonanStatus::Draft, $permohonan->fresh()->status);
    }

    public function test_submit_sats_ln_fails_when_a_sats_ln_is_missing_an_item(): void
    {
        $user = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);
        $permohonan = $satsLn->permohonan;

        $document = $this->createLampiranDocument($user);
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.documents.attach', [$permohonan, $satsLn]), [
            'document_id' => (string) $document->id,
        ]);

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.submit', $permohonan));

        $response->assertRedirect(route('permohonan-export.show', $permohonan));
        $this->assertNotNull(session('error'));
        $this->assertSame(PermohonanStatus::Draft, $permohonan->fresh()->status);
    }

    public function test_submit_sats_ln_fails_when_a_sats_ln_is_missing_a_document(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.submit', $permohonan));

        $response->assertRedirect(route('permohonan-export.show', $permohonan));
        $this->assertNotNull(session('error'));
        $this->assertSame(PermohonanStatus::Draft, $permohonan->fresh()->status);
    }

    public function test_submit_sats_ln_fails_when_exceeding_max_25_sats_ln(): void
    {
        $user = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);
        $permohonan = $satsLn->permohonan;

        for ($i = 0; $i < 25; $i++) {
            $this->addSatsLnToPermohonan($satsLn);
        }

        $this->assertSame(26, $permohonan->satsLn()->count());

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.submit', $permohonan));

        $response->assertRedirect(route('permohonan-export.show', $permohonan));
        $this->assertNotNull(session('error'));
        $this->assertSame(PermohonanStatus::Draft, $permohonan->fresh()->status);
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

    public function test_submit_notifies_verifiers_with_category_permohonan(): void
    {
        Notification::fake();

        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $this->actingAs($user)->post(route('permohonan-export.submit', $permohonan));

        Notification::assertSentTo(
            $verifier,
            AppNotification::class,
            fn (AppNotification $notification) => $notification->category === NotificationCategory::Permohonan
                && $notification->title === __('Permohonan SATS-LN Ekspor Baru')
        );
    }

    /**
     * submitSatsLn() sebelumnya (sebelum PermohonanStatusChanged ada) sama sekali tidak mengirim
     * notifikasi apapun - jadi ini bukan cuma regresi test, tapi menguji perilaku BARU.
     */
    public function test_submit_sats_ln_notifies_the_entitys_users_with_category_permohonan(): void
    {
        Notification::fake();

        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $satsLn = $permohonan->satsLn()->firstOrFail();

        $document = $this->createLampiranDocument($user);
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.documents.attach', [$permohonan, $satsLn]), [
            'document_id' => (string) $document->id,
        ]);

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.submit', $permohonan));

        Notification::assertSentTo(
            $user,
            AppNotification::class,
            fn (AppNotification $notification) => $notification->category === NotificationCategory::Permohonan
                && $notification->title === __('Permohonan Diajukan')
        );
    }

    /**
     * recomputeStatus() (dipanggil dari markPrinted/markSigned) sebelumnya mengubah status TANPA
     * notifikasi apapun - jadi ini juga menguji cakupan BARU dari PermohonanStatusChanged, bukan
     * cuma regresi.
     */
    public function test_mark_printed_notifies_the_submitting_entitys_users(): void
    {
        Notification::fake();

        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);

        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->satsLnPayload($kuotaPerusahaan, 10));
        $satsLn = $permohonan->satsLn()->firstOrFail();
        $this->actingAs($user)->post(route('permohonan-export.submit', $permohonan));
        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.verify', $permohonan));

        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.mark-printed', [$permohonan, $satsLn]));

        $this->assertSame(PermohonanStatus::DicetakSebagian, $permohonan->fresh()->status);
        Notification::assertSentTo(
            $user,
            AppNotification::class,
            fn (AppNotification $notification) => $notification->category === NotificationCategory::Permohonan
                && $notification->title === __('SATS-LN Dicetak')
        );
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

    /**
     * nomor_urut ditetapkan permanen saat SatsLn dibuat dan tidak boleh dipakai ulang meski
     * SatsLn di tengah urutan dihapus - lihat claude/permohonan-task.md.
     */
    public function test_sats_ln_nomor_urut_is_assigned_sequentially_and_stable_after_deletion(): void
    {
        $user = $this->makeUser('perusahaan');
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->minimalSatsLnPayload());
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->minimalSatsLnPayload());

        $satsLn1 = $permohonan->satsLn()->orderBy('id')->first();
        $satsLn2 = $permohonan->satsLn()->orderBy('id')->skip(1)->first();
        $this->assertSame(1, $satsLn1->nomor_urut);
        $this->assertSame(2, $satsLn2->nomor_urut);

        $this->actingAs($user)->delete(route('permohonan-export.sats-ln.destroy', [$permohonan, $satsLn2]));

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->minimalSatsLnPayload());
        $satsLn3 = $permohonan->satsLn()->whereNotIn('id', [$satsLn1->id, $satsLn2->id])->firstOrFail();

        $this->assertSame(3, $satsLn3->nomor_urut);
        $this->assertSame(1, $satsLn1->fresh()->nomor_urut);
    }

    /**
     * satsln_number_issued sengaja null saat dibuat (belum di-generate) - halaman preview dan
     * detail staf harus menampilkan hint bilingual, bukan nomor kosong - lihat
     * claude/permohonan-task.md.
     */
    public function test_new_sats_ln_has_null_permit_number_and_show_page_displays_the_hint(): void
    {
        $user = $this->makeUser('perusahaan');
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->minimalSatsLnPayload());
        $satsLn = $permohonan->satsLn()->firstOrFail();

        $this->assertNull($satsLn->satsln_number_issued);

        $this->actingAs($user)
            ->get(route('permohonan-export.sats-ln.show', [$permohonan, $satsLn]))
            ->assertOk()
            ->assertSee('Nomor akan otomatis di-generate');
    }

    /**
     * Icon edit di daftar SATS-LN kiri dipindahkan ke tombol "Edit SATS-LN" di navbar (yang
     * membuka modal untuk SatsLn yang sedang dipilih) - lihat claude/permohonan-task.md.
     */
    public function test_navbar_edit_button_opens_modal_for_selected_sats_ln_and_left_column_edit_icon_is_removed(): void
    {
        $user = $this->makeUser('perusahaan');
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->minimalSatsLnPayload());
        $satsLn = $permohonan->satsLn()->firstOrFail();

        $response = $this->actingAs($user)->get(route('permohonan-export.sats-ln.show', [$permohonan, $satsLn]));

        $response->assertOk();
        $response->assertDontSee('aria-label="Ubah"', false);
        $response->assertSee('data-hs-overlay="#satsln-edit-modal-'.$satsLn->id.'"', false);
        $response->assertSee(__('Edit SATS-LN'));
    }

    /**
     * Fitur cari daftar SATS-LN kiri (client-side) butuh nomor urut, customer, dan nama spesies
     * dirender ke data-search-haystack tiap baris - lihat claude/permohonan-task.md.
     */
    public function test_left_column_rows_expose_search_haystack_data_attribute(): void
    {
        $user = $this->makeUser('perusahaan');
        $singapore = Country::create(['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code3' => 'SGP', 'phone_code' => '65']);
        $customer = Customer::create(['name' => 'Buyer Serpentes', 'country_id' => $singapore->id, 'address' => 'Jl. Buyer', 'entity_id' => $user->entity_id]);
        $this->createPermohonan($user);
        $permohonan = Permohonan::firstOrFail();

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.store', $permohonan), $this->minimalSatsLnPayload([
            'customer_company_id' => (string) $customer->id,
        ]));
        $satsLn = $permohonan->satsLn()->firstOrFail();

        $response = $this->actingAs($user)->get(route('permohonan-export.show', $permohonan));

        $response->assertOk();
        $response->assertSee('data-satsln-search-item', false);
        $response->assertSee('data-satsln-search-input', false);
        $response->assertSee('data-search-haystack="1 buyer serpentes"', false);
    }

    public function test_show_page_displays_the_total_quota_spelled_out_in_the_users_active_locale(): void
    {
        $user = $this->makeUser('perusahaan');
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $kuotaPerusahaan->kuotaNasional->tradeCode->update(['unit_name' => 'Ekor']);

        $satsLn = $this->makeSatsLnForItems($user);
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;

        $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '20',
        ])->assertSessionHasNoErrors();

        // Default sesi (belum pernah pindah bahasa) mengikuti APP_LOCALE = id.
        $defaultLocaleResponse = $this->actingAs($user)
            ->get(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]));
        $defaultLocaleResponse->assertOk();
        $defaultLocaleResponse->assertSee('Dua Puluh Ekor', false);
        $defaultLocaleResponse->assertDontSee('Twenty Ekor', false);

        // Setelah user pindah ke bahasa Inggris (route locale.switch, disimpan di session), baris
        // Total ikut berubah - bukan lagi menampilkan kedua bahasa sekaligus.
        $this->actingAs($user)->get(route('locale.switch', 'en'));
        $englishLocaleResponse = $this->actingAs($user)
            ->get(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]));
        $englishLocaleResponse->assertOk();
        $englishLocaleResponse->assertSee('Twenty Ekor', false);
        $englishLocaleResponse->assertDontSee('Dua Puluh Ekor', false);
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
        $permohonan = Permohonan::where('entity_id', $user->entity_id)->latest('id')->firstOrFail();

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

    public function test_sats_ln_item_saves_description_and_shows_it_in_the_species_table(): void
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
            'description' => 'Hasil penangkaran generasi ke-2',
        ])->assertSessionHasNoErrors();

        $item = $satsLn->items()->firstOrFail();
        $this->assertSame('Hasil penangkaran generasi ke-2', $item->description);

        $this->actingAs($user)->get(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]))
            ->assertSee('Hasil penangkaran generasi ke-2');
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

    public function test_sats_ln_item_update_can_change_the_description(): void
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
            'description' => 'Deskripsi awal',
        ]);
        $item = $satsLn->items()->firstOrFail();

        $this->actingAs($user)->put(route('permohonan-export.sats-ln.items.update', [$satsLn->permohonan, $satsLn, $item]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '10',
            'description' => 'Deskripsi diperbarui',
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('Deskripsi diperbarui', $item->description);
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

    /**
     * Regresi: $komoditasForOptions/$tradeCodeForOptions/$speciesForOptions di
     * sats-ln-item-modal-form.blade.php sempat tidak dicek $isRedisplay - jadi begitu SATU modal
     * edit gagal validasi, modal edit item LAIN di halaman yang sama ikut salah menampilkan
     * opsi Komoditas/Trade Code/Species-nya (pakai old() milik modal yang gagal, bukan data
     * item-nya sendiri) meski select-nya sendiri tidak sedang diedit.
     */
    public function test_a_failed_edit_does_not_corrupt_other_items_edit_modal_options(): void
    {
        $user = $this->makeUser('perusahaan');
        $firstKuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100, 'Reptil A');
        $secondKuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100, 'Reptil B');
        $satsLn = $this->makeSatsLnForItems($user);
        $firstKuotaNasional = $firstKuotaPerusahaan->kuotaNasional;

        // Dua item, sama-sama komoditas "Reptil A" (sah).
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
        [$firstItem, $secondItem] = $satsLn->items()->orderBy('id')->get();

        // Coba ubah item PERTAMA ke komoditas "Reptil B" - gagal karena beda dari item KEDUA.
        $secondKuotaNasional = $secondKuotaPerusahaan->kuotaNasional;
        $this->actingAs($user)->from(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]))
            ->put(route('permohonan-export.sats-ln.items.update', [$satsLn->permohonan, $satsLn, $firstItem]), [
                '_modal' => 'satsln-item-edit-modal-'.$firstItem->id,
                'source_id' => (string) $secondKuotaNasional->source_id,
                'komoditas_id' => (string) $secondKuotaNasional->komoditas_id,
                'trade_code_id' => (string) $secondKuotaNasional->trade_code_id,
                'species_id' => (string) $secondKuotaNasional->species_id,
                'qty' => '5',
            ])
            ->assertSessionHasErrors('komoditas_id');

        $response = $this->actingAs($user)->get(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]));
        $response->assertOk();
        $content = $response->getContent();

        // Modal item PERTAMA (yang gagal) benar menampilkan ulang percobaannya ("Reptil B").
        $this->assertMatchesRegularExpression(
            '/satsln-item-edit-modal-'.$firstItem->id.'-komoditas".{1,800}?<option value="'.$secondKuotaNasional->komoditas_id.'"/s',
            $content
        );

        // Modal item KEDUA (bukan yang diedit) TETAP menampilkan komoditasnya sendiri ("Reptil
        // A"), bukan ikut tercemar oleh percobaan gagal item pertama.
        $this->assertMatchesRegularExpression(
            '/satsln-item-edit-modal-'.$secondItem->id.'-komoditas".{1,800}?<option value="'.$firstKuotaNasional->komoditas_id.'"/s',
            $content
        );
        $this->assertDoesNotMatchRegularExpression(
            '/satsln-item-edit-modal-'.$secondItem->id.'-komoditas".{1,800}?<option value="'.$secondKuotaNasional->komoditas_id.'"/s',
            $content
        );
    }

    /**
     * Tambah item lalu submit permohonan-nya. Item sudah dihitung terpakai sejak Draft (lihat
     * PermohonanService - cuma Ditolak yang dikecualikan), jadi langkah submit() di sini bukan
     * syarat realisasi bertambah - dipanggil supaya test yang butuh status lanjut (bukan Draft,
     * mis. untuk menguji reject) punya alur yang realistis.
     */
    private function addItemAndSubmit(User $user, SatsLn $satsLn, KuotaNasional $kuotaNasional, float $qty): void
    {
        $this->actingAs($user)->post(route('permohonan-export.sats-ln.items.store', [$satsLn->permohonan, $satsLn]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => (string) $qty,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('permohonan-export.submit', $satsLn->permohonan))->assertRedirect();
    }

    /**
     * SatsLn baru menunjuk ke Permohonan yang SUDAH ADA (dipakai untuk menguji beberapa SatsLn
     * dalam satu Permohonan, karena route store SATS-LN cuma bisa dipanggil sekali per Permohonan
     * lewat helper makeSatsLnForItems()).
     */
    private function addSatsLnToPermohonan(SatsLn $existing): SatsLn
    {
        $nomorUrut = (int) (SatsLn::query()->withTrashed()->where('permohonan_id', $existing->permohonan_id)->max('nomor_urut') ?? 0) + 1;

        return SatsLn::create([
            'permohonan_id' => $existing->permohonan_id,
            'nomor_urut' => $nomorUrut,
            'entity_id' => $existing->entity_id,
            'type_satsln' => 'Export',
            'purpose_of_transaction_id' => PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial'])->id,
            'satsln_issued_type' => 'P_0',
            'satsln_number_issued' => '26'.random_int(10000, 99999),
            'year' => 2026,
        ]);
    }

    /**
     * Kolom "Realisasi Kuota" adalah saldo BERJALAN (seperti mutasi rekening), bukan satu total
     * yang diulang di semua baris - reproduksi persis contoh yang diberikan user: SatsLn 1 qty 1
     * -> tampil 1, SatsLn 2 qty 2 -> tampil 3 (1+2), SatsLn 3 qty 10 -> tampil 13 (1+2+10), lalu
     * entitas lain qty 5 -> tampil 18 (1+2+10+5). Semua terjadi selagi Permohonan masih Draft.
     */
    public function test_realisasi_kuota_nasional_is_a_running_total_per_row_not_a_repeated_grand_total(): void
    {
        $service = app(PermohonanService::class);

        $userA = $this->makeUser('perusahaan');
        $kuotaPerusahaanA = $this->makeKuotaPerusahaan($userA->entity, 100);
        $kuotaNasional = $kuotaPerusahaanA->kuotaNasional;

        $satsLn1 = $this->makeSatsLnForItems($userA);
        $this->assertSame(PermohonanStatus::Draft, $satsLn1->permohonan->fresh()->status);

        $this->actingAs($userA)->post(route('permohonan-export.sats-ln.items.store', [$satsLn1->permohonan, $satsLn1]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '1',
        ])->assertSessionHasNoErrors();

        $satsLn2 = $this->addSatsLnToPermohonan($satsLn1);
        $this->actingAs($userA)->post(route('permohonan-export.sats-ln.items.store', [$satsLn1->permohonan, $satsLn2]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '2',
        ])->assertSessionHasNoErrors();

        $satsLn3 = $this->addSatsLnToPermohonan($satsLn1);
        $this->actingAs($userA)->post(route('permohonan-export.sats-ln.items.store', [$satsLn1->permohonan, $satsLn3]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '10',
        ])->assertSessionHasNoErrors();

        $userB = $this->makeUser('perusahaan');
        $this->makeKuotaPerusahaanForNasional($userB->entity, $kuotaNasional, 100);
        $satsLnB = $this->makeSatsLnForItems($userB);
        $this->actingAs($userB)->post(route('permohonan-export.sats-ln.items.store', [$satsLnB->permohonan, $satsLnB]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '5',
        ])->assertSessionHasNoErrors();

        // Total keseluruhan tetap benar (dipakai validasi sisa kuota, bukan tampilan per-baris).
        $this->assertSame(18.0, $service->realisasiKuotaNasional($kuotaNasional->fresh()));

        $item1 = $satsLn1->items()->firstOrFail();
        $item2 = $satsLn2->items()->firstOrFail();
        $item3 = $satsLn3->items()->firstOrFail();
        $itemB = $satsLnB->items()->firstOrFail();

        $runningTotals = $service->realisasiKuotaNasionalRunningTotalForItems(
            collect([$item1, $item2, $item3, $itemB])
        );

        $this->assertSame(1.0, $runningTotals[$item1->id]);
        $this->assertSame(3.0, $runningTotals[$item2->id]);
        $this->assertSame(13.0, $runningTotals[$item3->id]);
        $this->assertSame(18.0, $runningTotals[$itemB->id]);

        // Ditampilkan juga di halaman show masing-masing SatsLn (kolom "Realisasi Kuota") -
        // kuota_nasional totalnya 150 (lihat makeKuotaPerusahaan: quota + 50).
        $this->actingAs($userA)->get(route('permohonan-export.sats-ln.show', [$satsLn1->permohonan, $satsLn1]))
            ->assertSee('1,00', false)->assertSee('150,00', false);
        $this->actingAs($userA)->get(route('permohonan-export.sats-ln.show', [$satsLn1->permohonan, $satsLn2]))
            ->assertSee('3,00', false);
        $this->actingAs($userA)->get(route('permohonan-export.sats-ln.show', [$satsLn1->permohonan, $satsLn3]))
            ->assertSee('13,00', false);
        $this->actingAs($userB)->get(route('permohonan-export.sats-ln.show', [$satsLnB->permohonan, $satsLnB]))
            ->assertSee('18,00', false);
    }

    public function test_realisasi_kuota_nasional_accumulates_across_different_entities(): void
    {
        $service = app(PermohonanService::class);

        $userA = $this->makeUser('perusahaan');
        $kuotaPerusahaanA = $this->makeKuotaPerusahaan($userA->entity, 100);
        $kuotaNasional = $kuotaPerusahaanA->kuotaNasional;
        $satsLnA1 = $this->makeSatsLnForItems($userA);

        $this->addItemAndSubmit($userA, $satsLnA1, $kuotaNasional, 10);
        $this->assertSame(10.0, $service->realisasiKuotaNasional($kuotaNasional->fresh()));

        $userB = $this->makeUser('perusahaan');
        $this->makeKuotaPerusahaanForNasional($userB->entity, $kuotaNasional, 100);
        $satsLnB = $this->makeSatsLnForItems($userB);

        $this->addItemAndSubmit($userB, $satsLnB, $kuotaNasional, 5);
        $this->assertSame(15.0, $service->realisasiKuotaNasional($kuotaNasional->fresh()));

        // Entitas A mengajukan Varanus salvator lagi lewat Permohonan BARU (kuota nasional yang
        // sama) - rumus yang sama berlaku lagi (item 2 di Revisi Permohonan 4).
        $satsLnA2 = $this->makeSatsLnForItems($userA);
        $this->addItemAndSubmit($userA, $satsLnA2, $kuotaNasional, 3);

        $this->assertSame(18.0, $service->realisasiKuotaNasional($kuotaNasional->fresh()));
    }

    public function test_sats_ln_item_fails_when_qty_exceeds_sisa_kuota_nasional_even_if_company_quota_allows(): void
    {
        $userA = $this->makeUser('perusahaan');
        $kuotaPerusahaanA = $this->makeKuotaPerusahaan($userA->entity, 100);
        $kuotaNasional = $kuotaPerusahaanA->kuotaNasional;
        $satsLnA = $this->makeSatsLnForItems($userA);

        // Kuota nasional totalnya 150 (lihat makeKuotaPerusahaan: quota + 50). Entitas B mengajukan
        // & submit duluan sampai memakai 140 dari kuota nasional itu (kuota perusahaannya sendiri
        // 200 - test ini dibuat supaya entitas B yang membatasi, bukan entitas A).
        $userB = $this->makeUser('perusahaan');
        $this->makeKuotaPerusahaanForNasional($userB->entity, $kuotaNasional, 200);
        $satsLnB = $this->makeSatsLnForItems($userB);
        $this->addItemAndSubmit($userB, $satsLnB, $kuotaNasional, 140);

        // Sisa kuota nasional tinggal 10 (150 - 140). Kuota perusahaan A sendiri (100) masih jauh
        // cukup untuk qty 50, tapi kuota nasional bersama sudah tidak cukup - harus tetap gagal.
        $response = $this->actingAs($userA)->post(route('permohonan-export.sats-ln.items.store', [$satsLnA->permohonan, $satsLnA]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '50',
        ]);

        $response->assertSessionHasErrors('qty');
        $this->assertSame(0, $satsLnA->items()->count());

        // Qty dalam batas sisa kuota nasional (10) tetap berhasil.
        $this->actingAs($userA)->post(route('permohonan-export.sats-ln.items.store', [$satsLnA->permohonan, $satsLnA]), [
            'source_id' => (string) $kuotaNasional->source_id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'qty' => '10',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $satsLnA->items()->count());
    }

    public function test_rejecting_permohonan_returns_its_qty_to_the_national_quota_realization(): void
    {
        // "SATS-LN dibatalkan atau dihapus" (item 5, Revisi Permohonan 4): SatsLn cuma bisa
        // dihapus selagi Permohonan-nya masih editable (Draft/Ditolak - lihat
        // PermohonanStatus::isEditable()), dan Draft/Ditolak memang sudah tidak pernah dihitung
        // "terpakai" sama sekali (lihat realisasiKuotaNasional()) - jadi jalur "kembali"-nya
        // kuota di dunia nyata bukan lewat hapus data setelah submit (tidak mungkin, sudah
        // read-only), melainkan lewat verifier MENOLAK permohonan yang sudah diajukan: begitu
        // status pindah ke Ditolak, realisasinya otomatis hilang dari perhitungan nasional -
        // tanpa perlu menghapus baris SatsLnItem apa pun.
        $service = app(PermohonanService::class);

        $user = $this->makeUser('perusahaan');
        $verifier = $this->makeStaffVerifier();
        $kuotaPerusahaan = $this->makeKuotaPerusahaan($user->entity, 100);
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;
        $satsLn = $this->makeSatsLnForItems($user);

        $this->addItemAndSubmit($user, $satsLn, $kuotaNasional, 20);
        $this->assertSame(20.0, $service->realisasiKuotaNasional($kuotaNasional->fresh()));

        $this->actingAs($verifier)->post(route('permohonan-export.verifikasi.reject', $satsLn->permohonan), [
            'catatan_verifikasi' => 'Dokumen kurang lengkap.',
        ])->assertRedirect();

        $this->assertSame(0.0, $service->realisasiKuotaNasional($kuotaNasional->fresh()));

        // Permohonan yang ditolak kembali editable - SatsLn-nya sekarang bisa dihapus juga
        // (realisasinya tetap 0, sudah tidak berubah karena memang sudah tidak terhitung sejak
        // status jadi Ditolak).
        $this->actingAs($user)->delete(route('permohonan-export.sats-ln.destroy', [$satsLn->permohonan, $satsLn]))
            ->assertRedirect();

        $this->assertSame(0.0, $service->realisasiKuotaNasional($kuotaNasional->fresh()));
    }

    public function test_show_page_shows_a_single_lampiran_button_when_no_document_attached(): void
    {
        $user = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);

        $response = $this->actingAs($user)->get(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]));

        $response->assertOk();
        $response->assertSee('data-hs-overlay="#satsln-document-attach-modal"', false);
        $response->assertDontSee('aria-label="'.__('Lampiran Dokumen').'"', false);
    }

    public function test_can_attach_an_existing_document_to_a_sats_ln(): void
    {
        $user = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);
        $document = $this->createLampiranDocument($user);

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.documents.attach', [$satsLn->permohonan, $satsLn]), [
            'document_id' => (string) $document->id,
        ]);

        $response->assertRedirect(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]));
        $this->assertSame('Lampiran berhasil ditambahkan.', session('toast_success'));
        $this->assertTrue($satsLn->documents()->where('documents.id', $document->id)->exists());
    }

    public function test_cannot_attach_a_permohonan_type_document(): void
    {
        $user = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);
        $document = $this->createPermohonanDocument($user);

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.documents.attach', [$satsLn->permohonan, $satsLn]), [
            'document_id' => (string) $document->id,
        ]);

        $response->assertSessionHasErrors('document_id');
        $this->assertFalse($satsLn->documents()->where('documents.id', $document->id)->exists());
    }

    public function test_cannot_attach_a_document_belonging_to_another_entity(): void
    {
        $user = $this->makeUser('perusahaan');
        $stranger = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);
        $document = $this->createLampiranDocument($stranger);

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.documents.attach', [$satsLn->permohonan, $satsLn]), [
            'document_id' => (string) $document->id,
        ]);

        $response->assertSessionHasErrors('document_id');
        $this->assertFalse($satsLn->documents()->where('documents.id', $document->id)->exists());
    }

    public function test_cannot_attach_the_same_document_twice(): void
    {
        $user = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);
        $document = $this->createLampiranDocument($user);
        $satsLn->documents()->attach($document->id);

        $response = $this->actingAs($user)->post(route('permohonan-export.sats-ln.documents.attach', [$satsLn->permohonan, $satsLn]), [
            'document_id' => (string) $document->id,
        ]);

        $response->assertSessionHasErrors('document_id');
        $this->assertSame(1, $satsLn->documents()->where('documents.id', $document->id)->count());
    }

    public function test_show_page_shows_a_lampiran_dropdown_and_switcher_after_attaching_documents(): void
    {
        $user = $this->makeUser('perusahaan');
        $satsLn = $this->makeSatsLnForItems($user);
        $firstDocument = $this->createLampiranDocument($user);
        $secondDocument = $this->createLampiranDocument($user, DocumentType::Bap);
        $satsLn->documents()->attach([$firstDocument->id, $secondDocument->id]);

        $response = $this->actingAs($user)->get(route('permohonan-export.sats-ln.show', [$satsLn->permohonan, $satsLn]));

        $response->assertOk();
        $response->assertSee('aria-label="'.__('Lampiran Dokumen').'"', false);
        $response->assertSee('data-hs-overlay="#satsln-document-attach-modal"', false);
        $response->assertSee('data-hs-overlay="#satsln-document-view-modal"', false);
        $response->assertSee('data-satsln-document-select', false);
        $response->assertSee(route('documents.preview', $firstDocument), false);
        $response->assertSee(route('documents.preview', $secondDocument), false);
    }
}
