<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssesmentIsiStatus;
use App\Enums\NotificationCategory;
use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentAspek;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Assesment\AssesmentPertanyaan;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\User;
use App\Notifications\AppNotification;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssesmentIsiVerifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);

        Storage::fake('s3');
    }

    private function makeUser(string $entityType = 'perusahaan'): User
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

        $user->assignRole($entityType);

        return $user;
    }

    /**
     * Assesment dengan 2 pertanyaan (bobot 40%/60%) masing-masing 2 jawaban, dicakup ke satu
     * komoditas, dan satu AssesmentIsi berstatus Submitted dengan jawaban entitas sudah terisi.
     */
    private function makeSubmittedIsi(int $entitasId, Komoditas $komoditas): array
    {
        $assesment = Assesment::create(['judul' => 'Assesment '.uniqid()]);
        $aspek = AssesmentAspek::create(['nama' => 'Aspek '.uniqid()]);

        $pertanyaan1 = AssesmentPertanyaan::create([
            'assesment_id' => $assesment->id,
            'aspek_id' => $aspek->id,
            'pertanyaan' => 'Pertanyaan 1',
        ]);
        $pertanyaan1->komoditas()->attach($komoditas->id, ['bobot' => 40]);
        $p1Ya = $pertanyaan1->jawaban()->create(['teks_jawaban' => 'Ya', 'urutan' => 0]);
        $p1Ya->komoditas()->attach($komoditas->id, ['skor' => 10]);
        $p1Tidak = $pertanyaan1->jawaban()->create(['teks_jawaban' => 'Tidak', 'urutan' => 1]);
        $p1Tidak->komoditas()->attach($komoditas->id, ['skor' => 0]);

        $pertanyaan2 = AssesmentPertanyaan::create([
            'assesment_id' => $assesment->id,
            'aspek_id' => $aspek->id,
            'pertanyaan' => 'Pertanyaan 2',
        ]);
        $pertanyaan2->komoditas()->attach($komoditas->id, ['bobot' => 60]);
        $p2Ya = $pertanyaan2->jawaban()->create(['teks_jawaban' => 'Ya', 'urutan' => 0]);
        $p2Ya->komoditas()->attach($komoditas->id, ['skor' => 10]);
        $p2Tidak = $pertanyaan2->jawaban()->create(['teks_jawaban' => 'Tidak', 'urutan' => 1]);
        $p2Tidak->komoditas()->attach($komoditas->id, ['skor' => 0]);

        $isi = AssesmentIsi::create([
            'assesment_id' => $assesment->id,
            'entitas_id' => $entitasId,
            'komoditas_id' => $komoditas->id,
            'status' => AssesmentIsiStatus::Submitted,
            'submitted_at' => now(),
        ]);

        // Entitas menjawab "Ya" untuk pertanyaan 1 (skor 10) dan "Tidak" untuk pertanyaan 2 (skor 0).
        $isi->jawaban()->create(['pertanyaan_id' => $pertanyaan1->id, 'jawaban_entitas_id' => $p1Ya->id]);
        $isi->jawaban()->create(['pertanyaan_id' => $pertanyaan2->id, 'jawaban_entitas_id' => $p2Tidak->id]);

        return compact('isi', 'pertanyaan1', 'pertanyaan2', 'p1Ya', 'p1Tidak', 'p2Ya', 'p2Tidak');
    }

    public function test_verifier_confirming_all_answers_computes_expected_skor_and_issues_sertifikat(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('assesment-isi.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi, 'pertanyaan1' => $pertanyaan1, 'pertanyaan2' => $pertanyaan2, 'p1Ya' => $p1Ya, 'p2Tidak' => $p2Tidak] =
            $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        // Verifikator setuju dengan kedua jawaban entitas (tidak dikoreksi):
        // skor = 0.4 * 10 (jawaban Ya, pertanyaan 1) + 0.6 * 0 (jawaban Tidak, pertanyaan 2) = 4.0
        $response = $this->actingAs($verifier)->post(route('assesment-isi.verifikasi.complete', $isi), [
            'jawaban' => [
                $pertanyaan1->id => $p1Ya->id,
                $pertanyaan2->id => $p2Tidak->id,
            ],
        ]);

        $response->assertRedirect(route('assesment-isi.verifikasi.index'));

        $isi->refresh();
        $this->assertSame(AssesmentIsiStatus::Verified, $isi->status);
        $this->assertSame($verifier->id, $isi->verified_by);
        $this->assertNotNull($isi->verified_at);
        $this->assertEqualsWithDelta(4.0, (float) $isi->skor_total, 0.001);
        $this->assertNotNull($isi->sertifikat_path);
        Storage::disk('s3')->assertExists($isi->sertifikat_path);
    }

    public function test_verifier_correcting_an_answer_changes_the_skor(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('assesment-isi.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi, 'pertanyaan1' => $pertanyaan1, 'pertanyaan2' => $pertanyaan2, 'p1Ya' => $p1Ya, 'p2Ya' => $p2Ya] =
            $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        // Verifikator mengoreksi jawaban pertanyaan 2 dari "Tidak" (entitas) jadi "Ya":
        // skor = 0.4 * 10 + 0.6 * 10 = 10.0
        $this->actingAs($verifier)->post(route('assesment-isi.verifikasi.complete', $isi), [
            'jawaban' => [
                $pertanyaan1->id => $p1Ya->id,
                $pertanyaan2->id => $p2Ya->id,
            ],
        ]);

        $isi->refresh();
        $this->assertEqualsWithDelta(10.0, (float) $isi->skor_total, 0.001);

        $koreksi = $isi->jawaban()->where('pertanyaan_id', $pertanyaan2->id)->firstOrFail();
        $this->assertSame($p2Ya->id, $koreksi->jawaban_verifikator_id);
        $this->assertFalse($koreksi->isSesuai());
    }

    /**
     * jawaban_entitas_id dan jawaban_verifikator_id harus tetap dua kolom terpisah - koreksi
     * verifikator TIDAK BOLEH menimpa jawaban asli entitas. Entitas yang membuka detail
     * pengajuannya sendiri harus tetap melihat jawaban yang dia pilih, plus keterangan koreksi
     * verifikator terpisah (lihat resources/views/assesment-isi/show.blade.php).
     */
    public function test_original_jawaban_entitas_is_preserved_and_shown_alongside_koreksi_on_entitas_detail_page(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('assesment-isi.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi, 'pertanyaan1' => $pertanyaan1, 'pertanyaan2' => $pertanyaan2, 'p1Ya' => $p1Ya, 'p2Tidak' => $p2Tidak, 'p2Ya' => $p2Ya] =
            $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        $this->actingAs($verifier)->post(route('assesment-isi.verifikasi.complete', $isi), [
            'jawaban' => [
                $pertanyaan1->id => $p1Ya->id,
                $pertanyaan2->id => $p2Ya->id,
            ],
        ]);

        $koreksi = $isi->jawaban()->where('pertanyaan_id', $pertanyaan2->id)->firstOrFail();
        $this->assertSame($p2Tidak->id, $koreksi->jawaban_entitas_id, 'jawaban asli entitas tidak boleh berubah');
        $this->assertSame($p2Ya->id, $koreksi->jawaban_verifikator_id);

        $submitter->givePermissionTo('assesment-isi.view');

        $response = $this->actingAs($submitter)->get(route('assesment-isi.show', $isi));

        $response->assertOk();
        $response->assertSee(__('Dikoreksi verifikator menjadi'));
        // Jawaban asli entitas ("Tidak") tetap yang tercentang pada radio - koreksi cuma catatan
        // terpisah, bukan menggantikan pilihan radio-nya.
        $response->assertSee('name="jawaban['.$pertanyaan2->id.']" type="radio" value="'.$p2Tidak->id.'"', false);

        $content = $response->getContent();
        $radioPos = strpos($content, 'value="'.$p2Tidak->id.'"');
        $this->assertNotFalse($radioPos);
        $this->assertStringContainsString('checked', substr($content, max(0, $radioPos - 200), 200));
    }

    /**
     * Reproduksi bug nyata: previewBukti() memakai authorizeOwner() (cuma pemilik entitas) padahal
     * route-nya sendiri (permission:assesment-isi.view|verify|view-all) mengizinkan verifikator -
     * verifikator yang buka halaman detail verifikasi selalu dapat 403 saat pratinjau dokumen.
     */
    public function test_verifier_can_preview_bukti_dukung_without_403(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('assesment-isi.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi, 'pertanyaan1' => $pertanyaan1] = $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        $buktiDukung = $pertanyaan1->buktiDukung()->create(['label' => 'Surat Izin', 'urutan' => 0]);
        Storage::disk('s3')->put('assesment-isi/dummy.pdf', 'fake-pdf-content');
        $bukti = $isi->bukti()->create([
            'bukti_dukung_id' => $buktiDukung->id,
            'file_path' => 'assesment-isi/dummy.pdf',
            'original_filename' => 'dummy.pdf',
        ]);

        $this->actingAs($verifier)->get(route('assesment-isi.bukti.preview', $bukti))->assertRedirect();
    }

    public function test_completing_verification_notifies_the_submitting_entitys_users(): void
    {
        Notification::fake();

        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('assesment-isi.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi, 'pertanyaan1' => $pertanyaan1, 'pertanyaan2' => $pertanyaan2, 'p1Ya' => $p1Ya, 'p2Tidak' => $p2Tidak] =
            $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        $this->actingAs($verifier)->post(route('assesment-isi.verifikasi.complete', $isi), [
            'jawaban' => [
                $pertanyaan1->id => $p1Ya->id,
                $pertanyaan2->id => $p2Tidak->id,
            ],
        ]);

        Notification::assertSentTo(
            $submitter,
            AppNotification::class,
            fn (AppNotification $notification) => $notification->category === NotificationCategory::Permohonan
        );
    }

    public function test_verifikasi_index_and_show_pages_render(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('assesment-isi.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi] = $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        $this->actingAs($verifier)->get(route('assesment-isi.verifikasi.index'))
            ->assertOk()
            ->assertSee('Reptil');

        $this->actingAs($verifier)->get(route('assesment-isi.verifikasi.show', $isi))
            ->assertOk()
            ->assertSee('Pertanyaan 1')
            ->assertSee('Pertanyaan 2');
    }

    public function test_wizard_step_saves_single_koreksi_and_show_resumes_with_bukti_view_button(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('assesment-isi.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi, 'pertanyaan1' => $pertanyaan1, 'pertanyaan2' => $pertanyaan2, 'p1Tidak' => $p1Tidak] =
            $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        $buktiDukung = $pertanyaan2->buktiDukung()->create(['label' => 'Surat Izin', 'urutan' => 0]);
        $bukti = $isi->bukti()->create([
            'bukti_dukung_id' => $buktiDukung->id,
            'file_path' => 'assesment-isi/dummy.pdf',
            'original_filename' => 'dummy.pdf',
        ]);

        $this->actingAs($verifier)
            ->postJson(route('assesment-isi.verifikasi.jawaban', [$isi, $pertanyaan1]), ['jawaban_id' => $p1Tidak->id])
            ->assertOk();

        $this->assertSame($p1Tidak->id, $isi->jawaban()->where('pertanyaan_id', $pertanyaan1->id)->value('jawaban_verifikator_id'));
        $this->assertNull($isi->jawaban()->where('pertanyaan_id', $pertanyaan2->id)->value('jawaban_verifikator_id'));
        $this->assertSame(AssesmentIsiStatus::Submitted, $isi->fresh()->status);

        // Langkah 1 sudah tersimpan -> wizard dilanjutkan dari langkah ke-2 (index 1), yang punya tombol Lihat bukti.
        // Langkah 1 dikoreksi ke jawaban lain -> radio "tidak sesuai" langkah 1 tercentang; langkah 2 belum dipilih.
        $page = $this->actingAs($verifier)->get(route('assesment-isi.verifikasi.show', $isi));
        $this->assertMatchesRegularExpression('/<input checked[^>]*data-kesesuaian="'.$pertanyaan1->id.'"[^>]*value="tidak_sesuai"/', $page->getContent());
        $this->assertDoesNotMatchRegularExpression('/<input checked[^>]*data-kesesuaian="'.$pertanyaan2->id.'"/', $page->getContent());
        // Dropdown jawaban hanya aktif pada langkah "tidak sesuai" (langkah 1), langkah 2 tampil disabled.
        $this->assertDoesNotMatchRegularExpression('/<select disabled[^>]*name="jawaban\['.$pertanyaan1->id.'\]"/', $page->getContent());
        $this->assertMatchesRegularExpression('/<select disabled[^>]*name="jawaban\['.$pertanyaan2->id.'\]"/', $page->getContent());

        $page
            ->assertOk()
            ->assertSee('data-langkah-awal="1"', false)
            ->assertSee('data-bukti-view="'.route('assesment-isi.bukti.preview', $bukti).'"', false);
    }

    public function test_wizard_step_rejects_jawaban_from_another_pertanyaan_and_non_submitted_isi(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('assesment-isi.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi, 'pertanyaan1' => $pertanyaan1, 'p1Ya' => $p1Ya, 'p2Ya' => $p2Ya] =
            $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        $this->actingAs($verifier)
            ->postJson(route('assesment-isi.verifikasi.jawaban', [$isi, $pertanyaan1]), ['jawaban_id' => $p2Ya->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jawaban_id');

        $isi->update(['status' => AssesmentIsiStatus::Verified]);

        $this->actingAs($verifier)
            ->postJson(route('assesment-isi.verifikasi.jawaban', [$isi, $pertanyaan1]), ['jawaban_id' => $p1Ya->id])
            ->assertForbidden()
            ->assertJson(['message' => 'Assesment ini sudah tidak dapat diubah.']);

        $this->assertNull($isi->jawaban()->where('pertanyaan_id', $pertanyaan1->id)->value('jawaban_verifikator_id'));

        // Halaman wizard untuk assesment yang sudah diverifikasi tampil baca-saja (tanpa tombol simpan).
        $this->actingAs($verifier)->get(route('assesment-isi.verifikasi.show', $isi))
            ->assertOk()
            ->assertSee('data-readonly="1"', false)
            ->assertDontSee('Simpan &amp; Lanjut', false);
    }

    public function test_non_verifier_cannot_access_verifikasi_routes(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $stranger = $this->makeUser('perusahaan');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['isi' => $isi, 'pertanyaan1' => $pertanyaan1, 'p1Ya' => $p1Ya] = $this->makeSubmittedIsi($submitter->entity_id, $komoditas);

        $this->actingAs($stranger)->get(route('assesment-isi.verifikasi.index'))->assertForbidden();
        $this->actingAs($stranger)->get(route('assesment-isi.verifikasi.show', $isi))->assertForbidden();
        $this->actingAs($stranger)->postJson(route('assesment-isi.verifikasi.jawaban', [$isi, $pertanyaan1]), ['jawaban_id' => $p1Ya->id])->assertForbidden();
    }
}
