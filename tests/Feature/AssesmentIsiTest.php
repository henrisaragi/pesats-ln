<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssesmentIsiStatus;
use App\Enums\KomoditasEntitasStatus;
use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentAspek;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Assesment\AssesmentPertanyaan;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssesmentIsiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);

        Storage::fake('s3');
    }

    private function makeEntitasUser(string $entityType = 'perusahaan'): User
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
     * Assesment lengkap dengan 1 pertanyaan (2 jawaban) dan 1 bukti dukung, dicakup ke satu
     * komoditas.
     */
    private function makeAssesmentWithPertanyaan(Komoditas $komoditas): array
    {
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran '.uniqid()]);
        $aspek = AssesmentAspek::create(['nama' => 'Kepatuhan Regulasi '.uniqid()]);

        $pertanyaan = AssesmentPertanyaan::create([
            'assesment_id' => $assesment->id,
            'aspek_id' => $aspek->id,
            'pertanyaan' => 'Apakah entitas memiliki dokumen izin yang berlaku?',
        ]);

        $pertanyaan->komoditas()->attach($komoditas->id, ['bobot' => 100]);

        $jawabanYa = $pertanyaan->jawaban()->create(['teks_jawaban' => 'Ya', 'urutan' => 0]);
        $jawabanYa->komoditas()->attach($komoditas->id, ['skor' => 10]);

        $jawabanTidak = $pertanyaan->jawaban()->create(['teks_jawaban' => 'Tidak', 'urutan' => 1]);
        $jawabanTidak->komoditas()->attach($komoditas->id, ['skor' => 0]);

        $bukti = $pertanyaan->buktiDukung()->create(['label' => 'Surat Izin', 'urutan' => 0]);

        return compact('assesment', 'pertanyaan', 'jawabanYa', 'jawabanTidak', 'bukti');
    }

    private function verifyKomoditasFor(User $user, Komoditas $komoditas): void
    {
        KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'komoditas-entitas/dummy.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Verified,
        ]);
    }

    public function test_entitas_sees_only_assesments_covering_its_verified_komoditas(): void
    {
        $user = $this->makeEntitasUser();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $this->makeAssesmentWithPertanyaan($reptil);
        $this->makeAssesmentWithPertanyaan($mamalia);

        $this->verifyKomoditasFor($user, $reptil);

        $response = $this->actingAs($user)->get(route('assesment-isi.index'));

        $response->assertOk();
        $response->assertSee('Reptil');
        $response->assertDontSee('Mamalia');
    }

    public function test_entitas_can_start_answer_and_submit_a_complete_assesment(): void
    {
        $user = $this->makeEntitasUser();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['assesment' => $assesment, 'jawabanYa' => $jawabanYa, 'bukti' => $bukti] = $this->makeAssesmentWithPertanyaan($reptil);

        $this->verifyKomoditasFor($user, $reptil);

        $this->actingAs($user)->post(route('assesment-isi.store'), [
            'assesment_id' => $assesment->id,
            'komoditas_id' => $reptil->id,
        ])->assertRedirect();

        $isi = AssesmentIsi::where('entitas_id', $user->entity_id)->firstOrFail();
        $this->assertSame(AssesmentIsiStatus::Draft, $isi->status);

        $pertanyaan = $assesment->pertanyaan()->firstOrFail();

        $this->actingAs($user)->post(route('assesment-isi.update', $isi), [
            'jawaban' => [$pertanyaan->id => $jawabanYa->id],
        ])->assertRedirect(route('assesment-isi.show', $isi));

        $this->assertDatabaseHas('assesment_isi_jawaban', [
            'isi_id' => $isi->id,
            'pertanyaan_id' => $pertanyaan->id,
            'jawaban_entitas_id' => $jawabanYa->id,
        ]);

        $this->actingAs($user)->post(route('assesment-isi.bukti.upload', [$isi, $bukti->id]), [
            'file' => UploadedFile::fake()->create('surat-izin.pdf', 100, 'application/pdf'),
        ])->assertOk();

        $this->actingAs($user)->post(route('assesment-isi.submit', $isi), [
            'jawaban' => [$pertanyaan->id => $jawabanYa->id],
        ])->assertRedirect(route('assesment-isi.index'));

        $isi->refresh();
        $this->assertSame(AssesmentIsiStatus::Submitted, $isi->status);
        $this->assertNotNull($isi->submitted_at);
        // Bobot komoditas 100%, jawabanYa skor 10 -> skor_entitas = (100/100) * 10 = 10.0. Dihitung
        // dari jawaban_entitas_id saja, dibekukan sebelum ada koreksi verifikator.
        $this->assertEqualsWithDelta(10.0, (float) $isi->skor_entitas, 0.001);
    }

    /**
     * Reproduksi bug nyata: "Simpan Draft" dan "Submit Assesment" dulu dua form terpisah, jadi
     * mengklik Submit langsung (tanpa Simpan Draft dulu) selalu gagal validasi kelengkapan tanpa
     * ada pesan error yang tampil - terlihat seperti "tidak ada respon" bagi user. Sekarang satu
     * form yang sama (lihat assesment-isi/show.blade.php), jadi submit() harus bisa langsung
     * menyimpan jawaban yang baru dipilih DAN memvalidasi kelengkapan dalam satu request.
     */
    public function test_submit_saves_jawaban_from_the_same_request_without_a_prior_draft_save(): void
    {
        $user = $this->makeEntitasUser();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['assesment' => $assesment, 'jawabanYa' => $jawabanYa, 'bukti' => $bukti] = $this->makeAssesmentWithPertanyaan($reptil);

        $this->verifyKomoditasFor($user, $reptil);

        $this->actingAs($user)->post(route('assesment-isi.store'), [
            'assesment_id' => $assesment->id,
            'komoditas_id' => $reptil->id,
        ]);

        $isi = AssesmentIsi::where('entitas_id', $user->entity_id)->firstOrFail();
        $pertanyaan = $assesment->pertanyaan()->firstOrFail();

        $this->actingAs($user)->post(route('assesment-isi.bukti.upload', [$isi, $bukti->id]), [
            'file' => UploadedFile::fake()->create('surat-izin.pdf', 100, 'application/pdf'),
        ]);

        $this->assertDatabaseMissing('assesment_isi_jawaban', ['isi_id' => $isi->id]);

        $this->actingAs($user)->post(route('assesment-isi.submit', $isi), [
            'jawaban' => [$pertanyaan->id => $jawabanYa->id],
        ])->assertRedirect(route('assesment-isi.index'));

        $isi->refresh();
        $this->assertSame(AssesmentIsiStatus::Submitted, $isi->status);
        $this->assertDatabaseHas('assesment_isi_jawaban', [
            'isi_id' => $isi->id,
            'pertanyaan_id' => $pertanyaan->id,
            'jawaban_entitas_id' => $jawabanYa->id,
        ]);
    }

    public function test_show_page_renders_with_pertanyaan_and_bukti_upload_field(): void
    {
        $user = $this->makeEntitasUser();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['assesment' => $assesment] = $this->makeAssesmentWithPertanyaan($reptil);

        $this->verifyKomoditasFor($user, $reptil);

        $this->actingAs($user)->post(route('assesment-isi.store'), [
            'assesment_id' => $assesment->id,
            'komoditas_id' => $reptil->id,
        ]);

        $isi = AssesmentIsi::where('entitas_id', $user->entity_id)->firstOrFail();

        $this->actingAs($user)->get(route('assesment-isi.show', $isi))
            ->assertOk()
            ->assertSee('Apakah entitas memiliki dokumen izin yang berlaku?')
            ->assertSee('Surat Izin');
    }

    /**
     * Bukti dukung sengaja tidak wajib untuk submit (permintaan user) - cuma jawaban yang wajib
     * lengkap.
     */
    public function test_submit_succeeds_without_bukti_dukung_uploaded(): void
    {
        $user = $this->makeEntitasUser();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['assesment' => $assesment, 'jawabanYa' => $jawabanYa] = $this->makeAssesmentWithPertanyaan($reptil);

        $this->verifyKomoditasFor($user, $reptil);

        $this->actingAs($user)->post(route('assesment-isi.store'), [
            'assesment_id' => $assesment->id,
            'komoditas_id' => $reptil->id,
        ]);

        $isi = AssesmentIsi::where('entitas_id', $user->entity_id)->firstOrFail();
        $pertanyaan = $assesment->pertanyaan()->firstOrFail();

        $this->actingAs($user)->post(route('assesment-isi.submit', $isi), [
            'jawaban' => [$pertanyaan->id => $jawabanYa->id],
        ])->assertRedirect(route('assesment-isi.index'));

        $isi->refresh();
        $this->assertSame(AssesmentIsiStatus::Submitted, $isi->status);
    }

    public function test_submit_is_rejected_when_a_pertanyaan_is_unanswered(): void
    {
        $user = $this->makeEntitasUser();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['assesment' => $assesment] = $this->makeAssesmentWithPertanyaan($reptil);

        $this->verifyKomoditasFor($user, $reptil);

        $this->actingAs($user)->post(route('assesment-isi.store'), [
            'assesment_id' => $assesment->id,
            'komoditas_id' => $reptil->id,
        ]);

        $isi = AssesmentIsi::where('entitas_id', $user->entity_id)->firstOrFail();

        $this->actingAs($user)->post(route('assesment-isi.submit', $isi), [])
            ->assertSessionHasErrors();

        $isi->refresh();
        $this->assertSame(AssesmentIsiStatus::Draft, $isi->status);
    }

    public function test_cannot_start_the_same_assesment_komoditas_twice(): void
    {
        $user = $this->makeEntitasUser();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['assesment' => $assesment] = $this->makeAssesmentWithPertanyaan($reptil);

        $this->verifyKomoditasFor($user, $reptil);

        $this->actingAs($user)->post(route('assesment-isi.store'), [
            'assesment_id' => $assesment->id,
            'komoditas_id' => $reptil->id,
        ]);

        $this->actingAs($user)->post(route('assesment-isi.store'), [
            'assesment_id' => $assesment->id,
            'komoditas_id' => $reptil->id,
        ]);

        $this->assertSame(1, AssesmentIsi::where('entitas_id', $user->entity_id)->count());
    }

    public function test_cannot_start_assesment_for_an_unverified_komoditas(): void
    {
        $user = $this->makeEntitasUser();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        ['assesment' => $assesment] = $this->makeAssesmentWithPertanyaan($reptil);

        $this->actingAs($user)->post(route('assesment-isi.store'), [
            'assesment_id' => $assesment->id,
            'komoditas_id' => $reptil->id,
        ])->assertForbidden();
    }
}
