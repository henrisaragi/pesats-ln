<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Country;
use App\Models\Document;
use App\Models\Entitas\Entitas;
use App\Models\Permohonan;
use App\Models\PurposeOfTransaction;
use App\Models\SatsLn;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);

        Storage::fake('local');
    }

    private function makeStaff(): User
    {
        $user = User::create([
            'name' => 'Staff Test',
            'email' => uniqid('staff', true).'@example.test',
            'username' => uniqid('staff', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->givePermissionTo([
            'documents.view',
            'documents.view-all',
            'documents.create',
            'documents.update',
            'documents.delete',
        ]);

        return $user;
    }

    private function makeEntitas(): Entitas
    {
        return Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Test', 'address' => 'Jl. Test']);
    }

    /**
     * A user with full documents.* permissions except documents.view-all - can only see/manage
     * documents belonging to their own entity.
     */
    private function makeLimitedUser(Entitas $entitas): User
    {
        $user = User::create([
            'name' => 'Limited User',
            'email' => uniqid('limited', true).'@example.test',
            'username' => uniqid('limited', true),
            'password' => bcrypt('password'),
            'entity_id' => $entitas->id,
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->givePermissionTo(['documents.view', 'documents.create', 'documents.update', 'documents.delete']);

        return $user;
    }

    private function indonesiaId(): int
    {
        return Country::query()->firstOrCreate(
            ['iso_code' => 'ID'],
            ['name' => 'Indonesia', 'iso_code3' => 'IDN', 'phone_code' => '62']
        )->id;
    }

    private function otherCountryId(): int
    {
        return Country::create(['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code3' => 'SGP', 'phone_code' => '65'])->id;
    }

    private function basePayload(Entitas $entitas): array
    {
        return [
            'entity_id' => $entitas->id,
            'nomor_document' => 'DOC-'.uniqid(),
            'issued_date' => '2026-01-10',
            'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ];
    }

    public function test_staff_can_view_index(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->get(route('documents.index'))->assertOk();
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $user = User::create([
            'name' => 'No Perm',
            'email' => uniqid('noperm', true).'@example.test',
            'username' => uniqid('noperm', true),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('documents.index'))->assertForbidden();
    }

    public function test_country_is_forced_to_indonesia_for_non_free_document_types(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makeEntitas();
        $indonesiaId = $this->indonesiaId();
        $otherId = $this->otherCountryId();

        $response = $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($entitas),
            'type_document' => DocumentType::SertifikatStandar->value,
            'issued_country_id' => $otherId,
        ]);

        $response->assertRedirect(route('documents.index'));
        $this->assertDatabaseHas('documents', [
            'type_document' => DocumentType::SertifikatStandar->value,
            'issued_country_id' => $indonesiaId,
        ]);
    }

    public function test_country_can_be_freely_chosen_for_export_permit(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makeEntitas();
        $this->indonesiaId();
        $otherId = $this->otherCountryId();

        $response = $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($entitas),
            'type_document' => DocumentType::ExportPermit->value,
            'issued_country_id' => $otherId,
        ]);

        $response->assertRedirect(route('documents.index'));
        $this->assertDatabaseHas('documents', [
            'type_document' => DocumentType::ExportPermit->value,
            'issued_country_id' => $otherId,
        ]);
    }

    public function test_file_must_be_a_pdf(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makeEntitas();
        $this->indonesiaId();

        $response = $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($entitas),
            'type_document' => DocumentType::FormC->value,
            'issued_country_id' => $this->indonesiaId(),
            'file' => UploadedFile::fake()->create('dokumen.docx', 100, 'application/msword'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('documents', ['entity_id' => $entitas->id]);
    }

    public function test_file_cannot_exceed_5mb(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makeEntitas();
        $this->indonesiaId();

        $response = $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($entitas),
            'type_document' => DocumentType::FormC->value,
            'issued_country_id' => $this->indonesiaId(),
            'file' => UploadedFile::fake()->create('dokumen.pdf', 5121, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_document_can_be_linked_to_a_permohonan_and_multiple_sats_ln(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makeEntitas();
        $this->indonesiaId();

        $permohonan = Permohonan::create([
            'entity_id' => $entitas->id,
            'nomor_permohonan' => 'PMH-2026-000001',
            'status' => 'draft',
        ]);

        $purpose = PurposeOfTransaction::create(['code' => 'COM-'.uniqid(), 'name' => 'Komersial']);

        $satsLnA = SatsLn::create([
            'permohonan_id' => $permohonan->id,
            'entity_id' => $entitas->id,
            'type_satsln' => 'Export',
            'purpose_of_transaction_id' => $purpose->id,
            'satsln_issued_type' => 'P_0',
            'satsln_number_issued' => '2600001',
            'year' => 2026,
        ]);

        $satsLnB = SatsLn::create([
            'permohonan_id' => $permohonan->id,
            'entity_id' => $entitas->id,
            'type_satsln' => 'Export',
            'purpose_of_transaction_id' => $purpose->id,
            'satsln_issued_type' => 'P_0',
            'satsln_number_issued' => '2600002',
            'year' => 2026,
        ]);

        $response = $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($entitas),
            'type_document' => DocumentType::SatsLn->value,
            'issued_country_id' => $this->indonesiaId(),
            'permohonan_id' => $permohonan->id,
            'sats_ln_ids' => [$satsLnA->id, $satsLnB->id],
        ]);

        $response->assertRedirect(route('documents.index'));

        $document = Document::firstOrFail();
        $this->assertSame($permohonan->id, $document->permohonan_id);
        $this->assertCount(2, $document->satsLn);
    }

    public function test_updating_replaces_the_old_file(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makeEntitas();
        $this->indonesiaId();

        $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($entitas),
            'type_document' => DocumentType::FormC->value,
            'issued_country_id' => $this->indonesiaId(),
        ]);

        $document = Document::firstOrFail();
        $oldPath = $document->path;
        Storage::disk('local')->assertExists($oldPath);

        $this->actingAs($staff)->put(route('documents.update', $document), [
            'entity_id' => $entitas->id,
            'nomor_document' => 'DOC-UPDATED',
            'type_document' => DocumentType::FormC->value,
            'issued_date' => '2026-02-01',
            'issued_country_id' => $this->indonesiaId(),
            'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('documents.index'));

        $document->refresh();
        $this->assertNotSame($oldPath, $document->path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($document->path);
        $this->assertSame('DOC-UPDATED', $document->nomor_document);
    }

    public function test_destroy_deletes_the_record_and_file(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makeEntitas();
        $this->indonesiaId();

        $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($entitas),
            'type_document' => DocumentType::FormC->value,
            'issued_country_id' => $this->indonesiaId(),
        ]);

        $document = Document::firstOrFail();
        $path = $document->path;

        $this->actingAs($staff)->delete(route('documents.destroy', $document))
            ->assertRedirect(route('documents.index'));

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_user_without_view_all_only_sees_their_own_entitys_documents_in_the_index(): void
    {
        $ownEntitas = $this->makeEntitas();
        $otherEntitas = $this->makeEntitas();
        $limited = $this->makeLimitedUser($ownEntitas);
        $this->indonesiaId();

        $staff = $this->makeStaff();
        $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($ownEntitas),
            'type_document' => DocumentType::FormC->value,
            'issued_country_id' => $this->indonesiaId(),
        ]);
        $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($otherEntitas),
            'type_document' => DocumentType::FormC->value,
            'issued_country_id' => $this->indonesiaId(),
        ]);

        $response = $this->actingAs($limited)->get(route('documents.index'));

        $response->assertOk();
        $ids = $response->viewData('documents')->pluck('entity_id')->unique();
        $this->assertEquals([$ownEntitas->id], $ids->values()->all());
    }

    public function test_user_without_view_all_cannot_create_a_document_for_another_entity(): void
    {
        $ownEntitas = $this->makeEntitas();
        $otherEntitas = $this->makeEntitas();
        $limited = $this->makeLimitedUser($ownEntitas);
        $this->indonesiaId();

        $response = $this->actingAs($limited)->post(route('documents.store'), [
            ...$this->basePayload($otherEntitas),
            'type_document' => DocumentType::FormC->value,
            'issued_country_id' => $this->indonesiaId(),
        ]);

        $response->assertRedirect(route('documents.index'));
        $this->assertDatabaseHas('documents', ['entity_id' => $ownEntitas->id]);
        $this->assertDatabaseMissing('documents', ['entity_id' => $otherEntitas->id]);
    }

    public function test_user_without_view_all_cannot_update_another_entitys_document(): void
    {
        $ownEntitas = $this->makeEntitas();
        $otherEntitas = $this->makeEntitas();
        $limited = $this->makeLimitedUser($ownEntitas);
        $this->indonesiaId();

        $staff = $this->makeStaff();
        $this->actingAs($staff)->post(route('documents.store'), [
            ...$this->basePayload($otherEntitas),
            'type_document' => DocumentType::FormC->value,
            'issued_country_id' => $this->indonesiaId(),
        ]);
        $document = Document::firstOrFail();

        $this->actingAs($limited)->put(route('documents.update', $document), [
            'entity_id' => $otherEntitas->id,
            'nomor_document' => 'HACKED',
            'type_document' => DocumentType::FormC->value,
            'issued_date' => '2026-01-01',
            'issued_country_id' => $this->indonesiaId(),
        ])->assertForbidden();

        $this->actingAs($limited)->delete(route('documents.destroy', $document))->assertForbidden();
        $this->actingAs($limited)->get(route('documents.download', $document))->assertForbidden();
    }
}
