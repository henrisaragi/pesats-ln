<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssesmentIsiStatus;
use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Assesment\AssesmentPertanyaan;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AssesmentPertanyaanTransferTest extends TestCase
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

    private function createSourceAssesment(User $admin, Komoditas $reptil, Komoditas $mamalia): Assesment
    {
        $assesment = Assesment::create(['judul' => 'Assesment Sumber']);

        $this->actingAs($admin)->post(route('assesment.pertanyaan.store', $assesment), [
            'aspek_nama' => 'Kepatuhan Regulasi',
            'pertanyaan' => 'Apakah entitas memiliki dokumen izin yang berlaku?',
            'informasi' => 'Periksa masa berlaku dokumen.',
            'metode_verifikasi' => 'Cocokkan nomor dokumen.',
            'komoditas_id' => [$reptil->id, $mamalia->id],
            'bobot' => [$reptil->id => 40, $mamalia->id => 60],
            'jawaban' => [
                ['teks' => 'Ya', 'skor' => [$reptil->id => 10, $mamalia->id => 8]],
                ['teks' => 'Tidak', 'skor' => [$reptil->id => 0, $mamalia->id => 0]],
            ],
            'bukti_dukung' => ['Surat Izin', 'Foto Lokasi'],
        ])->assertRedirect();

        return $assesment;
    }

    private function jsonFile(array $data): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('pertanyaan.json', json_encode($data));
    }

    public function test_exported_json_can_be_imported_into_another_assesment(): void
    {
        $admin = $this->makeAdmin();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);
        $source = $this->createSourceAssesment($admin, $reptil, $mamalia);

        $export = $this->actingAs($admin)->get(route('assesment.pertanyaan.export', $source));

        $export->assertOk();
        $this->assertStringContainsString('attachment;', (string) $export->headers->get('Content-Disposition'));
        $export->assertJsonPath('pertanyaan.0.komoditas.0.code', 'RPT');
        $export->assertJsonPath('pertanyaan.0.jawaban.0.skor.MML', 8);

        $target = Assesment::create(['judul' => 'Assesment Tujuan']);

        $this->actingAs($admin)
            ->post(route('assesment.pertanyaan.import', $target), ['file' => $this->jsonFile($export->json())])
            ->assertRedirect(route('assesment.index'))
            ->assertSessionHas('success');

        $imported = AssesmentPertanyaan::query()
            ->where('assesment_id', $target->id)
            ->with(['aspek', 'komoditas', 'jawaban.komoditas', 'buktiDukung'])
            ->sole();

        $this->assertSame('Kepatuhan Regulasi', $imported->aspek->nama);
        $this->assertSame(60.0, (float) $imported->komoditas->firstWhere('id', $mamalia->id)->pivot->bobot);
        $this->assertSame(8.0, $imported->jawaban->firstWhere('teks_jawaban', 'Ya')->skorUntukKomoditas($mamalia->id));
        $this->assertSame(['Surat Izin', 'Foto Lokasi'], $imported->buktiDukung->sortBy('urutan')->pluck('label')->all());
        $this->assertSame(1, AssesmentPertanyaan::where('assesment_id', $source->id)->count());
    }

    public function test_import_rejects_unknown_komoditas_code_without_saving_anything(): void
    {
        $admin = $this->makeAdmin();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);
        $source = $this->createSourceAssesment($admin, $reptil, $mamalia);
        $data = $this->actingAs($admin)->get(route('assesment.pertanyaan.export', $source))->json();
        $data['pertanyaan'][0]['komoditas'][0]['code'] = 'XXX';

        $target = Assesment::create(['judul' => 'Assesment Tujuan']);

        $this->actingAs($admin)
            ->post(route('assesment.pertanyaan.import', $target), ['file' => $this->jsonFile($data), '_modal' => 'assesment-import-modal-'.$target->id])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, AssesmentPertanyaan::where('assesment_id', $target->id)->count());
    }

    public function test_import_rejects_non_export_json(): void
    {
        $admin = $this->makeAdmin();
        $target = Assesment::create(['judul' => 'Assesment Tujuan']);

        $this->actingAs($admin)
            ->post(route('assesment.pertanyaan.import', $target), ['file' => $this->jsonFile(['foo' => 'bar'])])
            ->assertSessionHasErrors('file');
    }

    public function test_import_is_blocked_once_assesment_has_a_submission(): void
    {
        $admin = $this->makeAdmin();
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);
        $source = $this->createSourceAssesment($admin, $reptil, $mamalia);
        $data = $this->actingAs($admin)->get(route('assesment.pertanyaan.export', $source))->json();

        AssesmentIsi::create([
            'assesment_id' => $source->id,
            'entitas_id' => Entitas::create(['entity_type' => 'perusahaan', 'name' => 'Entitas Test', 'address' => 'Jl. Test'])->id,
            'komoditas_id' => $reptil->id,
            'status' => AssesmentIsiStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->post(route('assesment.pertanyaan.import', $source), ['file' => $this->jsonFile($data)])
            ->assertSessionHas('error');

        $this->assertSame(1, AssesmentPertanyaan::where('assesment_id', $source->id)->count());
    }
}
