<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssesmentIsiStatus;
use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentAspek;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Assesment\AssesmentPertanyaan;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssesmentPertanyaanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => uniqid('admin', true).'@example.test',
            'username' => uniqid('admin', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->givePermissionTo(['assesment.view', 'assesment.create', 'assesment.update', 'assesment.delete']);

        return $user;
    }

    private function payload(int $komoditas1, int $komoditas2, string $aspekNama = 'Kepatuhan Regulasi'): array
    {
        return [
            'aspek_nama' => $aspekNama,
            'pertanyaan' => 'Apakah entitas memiliki dokumen izin yang berlaku?',
            'informasi' => 'Periksa masa berlaku dokumen.',
            'metode_verifikasi' => 'Cocokkan nomor dokumen dengan sistem OSS.',
            'komoditas_id' => [$komoditas1, $komoditas2],
            'bobot' => [$komoditas1 => 40, $komoditas2 => 60],
            'jawaban' => [
                ['teks' => 'Ya', 'skor' => [$komoditas1 => 10, $komoditas2 => 8]],
                ['teks' => 'Tidak', 'skor' => [$komoditas1 => 0, $komoditas2 => 0]],
            ],
            'bukti_dukung' => ['Surat Izin'],
        ];
    }

    public function test_assesment_index_and_pertanyaan_builder_pages_render(): void
    {
        $admin = $this->makeAdmin();
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran']);
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $this->actingAs($admin)->post(route('assesment.pertanyaan.store', $assesment), $this->payload($reptil->id, $mamalia->id));

        $this->actingAs($admin)->get(route('assesment.index'))->assertOk()->assertSee('Assesment Standar Peredaran');
        $this->actingAs($admin)->get(route('assesment.pertanyaan.index', $assesment))->assertOk()->assertSee('Surat Izin');
    }

    public function test_aspek_search_typeahead_returns_matches(): void
    {
        $admin = $this->makeAdmin();
        AssesmentAspek::create(['nama' => 'Kepatuhan Regulasi']);
        AssesmentAspek::create(['nama' => 'Tata Kelola Populasi']);

        $response = $this->actingAs($admin)->getJson(route('assesment.aspek.search', ['q' => 'kepatuhan']));

        $response->assertOk();
        $response->assertJsonFragment(['nama' => 'Kepatuhan Regulasi']);
        $response->assertJsonMissing(['nama' => 'Tata Kelola Populasi']);
    }

    public function test_admin_can_create_pertanyaan_with_bobot_and_skor_per_komoditas(): void
    {
        $admin = $this->makeAdmin();
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran']);
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $response = $this->actingAs($admin)->post(
            route('assesment.pertanyaan.store', $assesment),
            $this->payload($reptil->id, $mamalia->id)
        );

        $response->assertRedirect(route('assesment.pertanyaan.index', $assesment));

        $pertanyaan = AssesmentPertanyaan::where('assesment_id', $assesment->id)->firstOrFail();

        $this->assertSame(2, $pertanyaan->komoditas()->count());
        $this->assertEquals(40, $pertanyaan->komoditas()->where('komoditas.id', $reptil->id)->first()->pivot->bobot);
        $this->assertEquals(60, $pertanyaan->komoditas()->where('komoditas.id', $mamalia->id)->first()->pivot->bobot);

        $jawabanYa = $pertanyaan->jawaban()->where('teks_jawaban', 'Ya')->firstOrFail();
        $this->assertSame(10.0, $jawabanYa->skorUntukKomoditas($reptil->id));
        $this->assertSame(8.0, $jawabanYa->skorUntukKomoditas($mamalia->id));

        $this->assertSame(1, $pertanyaan->buktiDukung()->count());
        $this->assertSame('Surat Izin', $pertanyaan->buktiDukung()->first()->label);
    }

    /**
     * Field bobot/skor dirender untuk SEMUA komoditas di halaman builder, disembunyikan via CSS
     * untuk yang tidak dicentang (bukan dihapus dari DOM) - browser tetap mengirim entry kosong
     * untuk komoditas yang tidak dicentang. Reproduksi bug nyata yang ketahuan lewat smoke test
     * browser: request programatik (seperti test lain di file ini) tidak menyertakan entry kosong
     * itu, jadi tidak menangkap bug-nya - test ini sengaja menyertakan supaya regresi tidak lolos.
     */
    public function test_unchecked_komoditas_hidden_fields_are_ignored_not_validated(): void
    {
        $admin = $this->makeAdmin();
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran']);
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);
        $burung = Komoditas::create(['name' => 'Burung', 'code' => 'BRG']);

        $payload = $this->payload($reptil->id, $mamalia->id);
        // Hidden field kosong untuk komoditas yang TIDAK dicentang (Burung) - persis seperti yang
        // dikirim browser sungguhan, bukan cuma yang dicentang seperti di test lain.
        $payload['bobot'][$burung->id] = '';
        $payload['jawaban'][0]['skor'][$burung->id] = '';
        $payload['jawaban'][1]['skor'][$burung->id] = '';

        $response = $this->actingAs($admin)->post(route('assesment.pertanyaan.store', $assesment), $payload);

        $response->assertRedirect(route('assesment.pertanyaan.index', $assesment));

        $pertanyaan = AssesmentPertanyaan::where('assesment_id', $assesment->id)->firstOrFail();
        $this->assertSame(2, $pertanyaan->komoditas()->count());
        $this->assertFalse($pertanyaan->komoditas()->where('komoditas.id', $burung->id)->exists());
    }

    public function test_existing_aspek_is_reused_case_insensitively_instead_of_duplicated(): void
    {
        $admin = $this->makeAdmin();
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran']);
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $this->actingAs($admin)->post(
            route('assesment.pertanyaan.store', $assesment),
            $this->payload($reptil->id, $mamalia->id, 'Kepatuhan Regulasi')
        );

        $this->actingAs($admin)->post(
            route('assesment.pertanyaan.store', $assesment),
            $this->payload($reptil->id, $mamalia->id, 'kepatuhan regulasi')
        );

        $this->assertSame(1, AssesmentAspek::count());
        $this->assertSame(2, AssesmentPertanyaan::where('assesment_id', $assesment->id)->count());
    }

    public function test_edit_page_renders_with_existing_values(): void
    {
        $admin = $this->makeAdmin();
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran']);
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $this->actingAs($admin)->post(route('assesment.pertanyaan.store', $assesment), $this->payload($reptil->id, $mamalia->id));
        $pertanyaan = AssesmentPertanyaan::where('assesment_id', $assesment->id)->firstOrFail();

        $this->actingAs($admin)->get(route('assesment.pertanyaan.index', $assesment))
            ->assertSee(route('assesment.pertanyaan.edit', [$assesment, $pertanyaan]));

        $this->actingAs($admin)->get(route('assesment.pertanyaan.edit', [$assesment, $pertanyaan]))
            ->assertOk()
            ->assertSee('Apakah entitas memiliki dokumen izin yang berlaku?')
            ->assertSee('name="jawaban[1][teks]"', false)
            ->assertSee('value="Tidak"', false)
            ->assertSee('Surat Izin');
    }

    public function test_admin_can_update_pertanyaan_jawaban_and_bobot(): void
    {
        $admin = $this->makeAdmin();
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran']);
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $this->actingAs($admin)->post(route('assesment.pertanyaan.store', $assesment), $this->payload($reptil->id, $mamalia->id));
        $pertanyaan = AssesmentPertanyaan::where('assesment_id', $assesment->id)->firstOrFail();

        $payload = [
            'aspek_nama' => 'Kepatuhan Regulasi',
            'pertanyaan' => 'Pertanyaan yang sudah direvisi',
            'informasi' => null,
            'metode_verifikasi' => null,
            'komoditas_id' => [$reptil->id],
            'bobot' => [$reptil->id => 75, $mamalia->id => ''],
            'jawaban' => [
                ['teks' => 'Lengkap', 'skor' => [$reptil->id => 5, $mamalia->id => '']],
                ['teks' => 'Sebagian', 'skor' => [$reptil->id => 3, $mamalia->id => '']],
                ['teks' => 'Tidak Ada', 'skor' => [$reptil->id => 0, $mamalia->id => '']],
            ],
            'bukti_dukung' => ['Dokumen Baru'],
        ];

        $this->actingAs($admin)->put(route('assesment.pertanyaan.update', [$assesment, $pertanyaan]), $payload)
            ->assertRedirect(route('assesment.pertanyaan.index', $assesment));

        $pertanyaan->refresh();
        $this->assertSame('Pertanyaan yang sudah direvisi', $pertanyaan->pertanyaan);
        $this->assertSame([$reptil->id], $pertanyaan->komoditas()->pluck('komoditas.id')->all());
        $this->assertEquals(75, $pertanyaan->komoditas()->first()->pivot->bobot);
        $this->assertSame(['Lengkap', 'Sebagian', 'Tidak Ada'], $pertanyaan->jawaban()->pluck('teks_jawaban')->all());
        $this->assertSame(3.0, $pertanyaan->jawaban()->where('teks_jawaban', 'Sebagian')->firstOrFail()->skorUntukKomoditas($reptil->id));
        $this->assertSame(['Dokumen Baru'], $pertanyaan->buktiDukung()->pluck('label')->all());
        $this->assertSame(1, AssesmentAspek::count());
    }

    public function test_pertanyaan_structure_is_locked_once_an_entitas_has_a_submission(): void
    {
        $admin = $this->makeAdmin();
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran']);
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        AssesmentIsi::create([
            'assesment_id' => $assesment->id,
            'entitas_id' => Entitas::create([
                'entity_type' => 'perusahaan',
                'name' => 'Entitas Test',
                'address' => 'Jl. Test',
            ])->id,
            'komoditas_id' => $reptil->id,
            'status' => AssesmentIsiStatus::Draft,
        ]);

        $this->actingAs($admin)->post(
            route('assesment.pertanyaan.store', $assesment),
            $this->payload($reptil->id, $mamalia->id)
        )->assertForbidden();
    }

    public function test_pertanyaan_cannot_be_edited_once_an_entitas_has_a_submission(): void
    {
        $admin = $this->makeAdmin();
        $assesment = Assesment::create(['judul' => 'Assesment Standar Peredaran']);
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $this->actingAs($admin)->post(route('assesment.pertanyaan.store', $assesment), $this->payload($reptil->id, $mamalia->id));
        $pertanyaan = AssesmentPertanyaan::where('assesment_id', $assesment->id)->firstOrFail();

        AssesmentIsi::create([
            'assesment_id' => $assesment->id,
            'entitas_id' => Entitas::create([
                'entity_type' => 'perusahaan',
                'name' => 'Entitas Test',
                'address' => 'Jl. Test',
            ])->id,
            'komoditas_id' => $reptil->id,
            'status' => AssesmentIsiStatus::Draft,
        ]);

        $this->actingAs($admin)->get(route('assesment.pertanyaan.edit', [$assesment, $pertanyaan]))->assertForbidden();
        $this->actingAs($admin)->put(
            route('assesment.pertanyaan.update', [$assesment, $pertanyaan]),
            $this->payload($reptil->id, $mamalia->id)
        )->assertForbidden();
    }
}
