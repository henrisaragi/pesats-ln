<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KomoditasEntitasStatus;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\DokumenVerifikasi\KomoditasEntitasAutoVerifikasiService;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Dokumen PDF di sini dibuat manual lewat buildSertifikatPdf() (bukan file PDF asli) - berisi
 * xref table yang valid dan satu content stream teks biasa, ditambah object /Type /Sig berisi
 * PKCS7 SignedData SUNGGUHAN (dibuat lewat openssl_pkcs7_sign() dari sertifikat X.509 yang
 * di-generate saat test jalan, lihat buildPkcs7SignatureHex()) kalau $withSignature true. Ini
 * supaya PdfSignatureChecker::extractIssuers() betul-betul mem-parsing PKCS7/X.509 asli, bukan
 * placeholder - lihat claude/otomatis-verifikasi.md bagian Revisi Fitur (DN Penerbit dicek dari
 * hasil verifikasi https://tte.komdigi.go.id/verifyPDF).
 */
class KomoditasEntitasAutoVerifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);

        Storage::fake('s3');
    }

    private function makeUser(string $entityType, string $entityName = 'Entitas Test'): User
    {
        $entity = Entitas::create([
            'entity_type' => $entityType,
            'name' => $entityName,
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
     * @return list<string>
     */
    private function validSertifikatLines(string $namaEntitas, string $namaKomoditas, string $nomorSertifikat, string $tanggalTerbitText): array
    {
        return [
            'SERTIFIKAT STANDAR',
            "Nomor: {$nomorSertifikat}",
            "Nama Pelaku Usaha: {$namaEntitas}",
            "Jenis {$namaKomoditas} Rusa Timor (Cervus timorensis)",
            'Ruang Lingkup: Peredaran jenis tumbuhan dan satwa liar luar negeri',
            "Diterbitkan Tanggal: {$tanggalTerbitText}",
        ];
    }

    /**
     * @param  list<string>  $lines
     * @param  array{cn: string, o: string, c: string}  $issuer  DN Penerbit sertifikat penandatangan yang mau disematkan.
     */
    private function buildSertifikatPdf(
        array $lines,
        bool $withSignature = true,
        array $issuer = ['cn' => 'BSRE CA DS G1', 'o' => 'Badan Siber dan Sandi Negara', 'c' => 'ID'],
    ): string {
        $content = "BT /F1 12 Tf 50 700 Td\n";

        foreach ($lines as $i => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= ($i === 0 ? '' : "0 -14 Td\n")."({$escaped}) Tj\n";
        }

        $content .= 'ET';
        $length = strlen($content);

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            5 => "<< /Length {$length} >>\nstream\n{$content}\nendstream",
        ];

        if ($withSignature) {
            $sigHex = $this->buildPkcs7SignatureHex($issuer['cn'], $issuer['o'], $issuer['c']);
            $objects[6] = "<< /Type /Sig /Filter /Adobe.PPKLite /SubFilter /adbe.pkcs7.detached /ByteRange [0 100 200 300] /Contents <{$sigHex}> >>";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $count = count($objects) + 1;
        $xref = "xref\n0 {$count}\n".sprintf("%010d %05d f \n", 0, 65535);

        foreach ($objects as $number => $offset) {
            $xref .= sprintf("%010d %05d n \n", $offsets[$number], 0);
        }

        $pdf .= $xref;
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }

    /**
     * Bikin sertifikat CA "palsu" dengan Subject DN = $issuerCn/$issuerO/$issuerC, terbitkan
     * sertifikat leaf yang ditandatangani CA itu (jadi Issuer-nya leaf = DN yang diminta), lalu
     * PKCS7-sign data dummy pakai leaf itu. Hex dari DER PKCS7 SignedData yang dihasilkan
     * ditaruh di /Contents dictionary /Sig - meniru struktur tanda tangan digital PDF asli
     * secukupnya supaya PdfSignatureChecker::extractIssuers() bisa mem-parsingnya sungguhan.
     */
    private function buildPkcs7SignatureHex(string $issuerCn, string $issuerO, string $issuerC): string
    {
        $caKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $caCsr = openssl_csr_new([
            'commonName' => $issuerCn,
            'organizationName' => $issuerO,
            'countryName' => $issuerC,
        ], $caKey);
        $caCert = openssl_csr_sign($caCsr, null, $caKey, 3650);

        $leafKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $leafCsr = openssl_csr_new(['commonName' => 'Test Signer', 'organizationName' => 'Test', 'countryName' => 'ID'], $leafKey);
        $leafCert = openssl_csr_sign($leafCsr, $caCert, $caKey, 365);

        $leafCertPem = '';
        openssl_x509_export($leafCert, $leafCertPem);
        $leafKeyPem = '';
        openssl_pkey_export($leafKey, $leafKeyPem);

        $dataFile = tempnam(sys_get_temp_dir(), 'otomatis-verifikasi-test-data-');
        $sigFile = tempnam(sys_get_temp_dir(), 'otomatis-verifikasi-test-sig-');

        try {
            file_put_contents($dataFile, 'dummy signed content');
            openssl_pkcs7_sign($dataFile, $sigFile, $leafCertPem, $leafKeyPem, [], PKCS7_DETACHED | PKCS7_BINARY);

            $smime = file_get_contents($sigFile);
            preg_match_all('/\n\n([A-Za-z0-9+\/=\r\n]{100,})\n--/', (string) $smime, $matches);
            $base64Body = end($matches[1]);
            $der = base64_decode(str_replace(["\r", "\n"], '', (string) $base64Body));

            return bin2hex($der);
        } finally {
            @unlink($dataFile);
            @unlink($sigFile);
        }
    }

    public function test_store_auto_verifies_when_all_checks_pass(): void
    {
        Notification::fake();

        $user = $this->makeUser('perusahaan', 'PT Sylva Restu Abadi');
        $komoditas = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('komoditas-entitas.verify');

        // Nama di dokumen sengaja pakai titik ("PT.") - harus tetap cocok dengan nama entitas
        // ("PT Sylva Restu Abadi" tanpa titik), lihat claude/otomatis-verifikasi.md poin Nama
        // Pelaku Usaha.
        $pdf = $this->buildSertifikatPdf($this->validSertifikatLines(
            'PT. Sylva Restu Abadi', 'Mamalia', 'STD-001/OSS/2026', '10 Januari 2026'
        ));

        $response = $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'nomor_sertifikat' => 'STD-001/OSS/2026',
            'tanggal_terbit' => '2026-01-10',
            'file' => UploadedFile::fake()->createWithContent('sertifikat.pdf', $pdf),
        ]);

        $response->assertRedirect(route('komoditas-entitas.index'));
        $response->assertSessionHas('success', __('Komoditas entitas berhasil diajukan dan lolos verifikasi otomatis.'));

        $record = KomoditasEntitas::where('entitas_id', $user->entity_id)->firstOrFail();
        $this->assertSame(KomoditasEntitasStatus::Verified, $record->status);
        $this->assertNull($record->verified_by);
        $this->assertNotNull($record->verified_at);
        $this->assertTrue($record->isVerifiedOtomatis());
        $this->assertTrue($record->summary_cek['valid']);
        $this->assertTrue(collect($record->summary_cek['checks'])->every(fn (array $check) => $check['status'] === 'lolos'));

        Notification::assertSentTo(
            $user,
            AppNotification::class,
            fn (AppNotification $n) => $n->title === __('Komoditas Entitas Terverifikasi Otomatis')
        );
        Notification::assertNotSentTo($verifier, AppNotification::class);
    }

    public function test_store_stays_pending_when_document_has_no_digital_signature(): void
    {
        Notification::fake();

        $user = $this->makeUser('perusahaan', 'PT Sylva Restu Abadi');
        $komoditas = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('komoditas-entitas.verify');

        $pdf = $this->buildSertifikatPdf($this->validSertifikatLines(
            'PT Sylva Restu Abadi', 'Mamalia', 'STD-002/OSS/2026', '11 Januari 2026'
        ), withSignature: false);

        $response = $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'nomor_sertifikat' => 'STD-002/OSS/2026',
            'tanggal_terbit' => '2026-01-11',
            'file' => UploadedFile::fake()->createWithContent('sertifikat.pdf', $pdf),
        ]);

        $response->assertSessionHas('success', __('Komoditas entitas berhasil diajukan, menunggu verifikasi.'));

        $record = KomoditasEntitas::where('entitas_id', $user->entity_id)->firstOrFail();
        $this->assertSame(KomoditasEntitasStatus::Pending, $record->status);
        $this->assertFalse($record->summary_cek['valid']);
        $this->assertSame('gagal', $record->summary_cek['checks'][0]['status']);
        $this->assertSame('dilewati', $record->summary_cek['checks'][1]['status']);

        Notification::assertSentTo($verifier, AppNotification::class);
        Notification::assertNotSentTo($user, AppNotification::class);
    }

    public function test_store_stays_pending_when_entitas_name_does_not_match_document(): void
    {
        $user = $this->makeUser('perusahaan', 'PT Berbeda Sekali');
        $komoditas = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $pdf = $this->buildSertifikatPdf($this->validSertifikatLines(
            'PT Sylva Restu Abadi', 'Mamalia', 'STD-003/OSS/2026', '12 Januari 2026'
        ));

        $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'nomor_sertifikat' => 'STD-003/OSS/2026',
            'tanggal_terbit' => '2026-01-12',
            'file' => UploadedFile::fake()->createWithContent('sertifikat.pdf', $pdf),
        ])->assertRedirect(route('komoditas-entitas.index'));

        $record = KomoditasEntitas::where('entitas_id', $user->entity_id)->firstOrFail();
        $this->assertSame(KomoditasEntitasStatus::Pending, $record->status);

        $checks = collect($record->summary_cek['checks'])->keyBy('key');
        $this->assertSame('lolos', $checks['tanda_tangan_digital']['status']);
        $this->assertSame('lolos', $checks['penerbit']['status']);
        $this->assertSame('gagal', $checks['nama_pelaku_usaha']['status']);
    }

    public function test_store_stays_pending_when_penerbit_is_not_bsre(): void
    {
        $user = $this->makeUser('perusahaan', 'PT Sylva Restu Abadi');
        $komoditas = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $pdf = $this->buildSertifikatPdf(
            $this->validSertifikatLines('PT Sylva Restu Abadi', 'Mamalia', 'STD-005/OSS/2026', '13 Januari 2026'),
            issuer: ['cn' => 'CA Abal-Abal', 'o' => 'Perusahaan Sendiri', 'c' => 'ID'],
        );

        $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'nomor_sertifikat' => 'STD-005/OSS/2026',
            'tanggal_terbit' => '2026-01-13',
            'file' => UploadedFile::fake()->createWithContent('sertifikat.pdf', $pdf),
        ])->assertRedirect(route('komoditas-entitas.index'));

        $record = KomoditasEntitas::where('entitas_id', $user->entity_id)->firstOrFail();
        $this->assertSame(KomoditasEntitasStatus::Pending, $record->status);

        $checks = collect($record->summary_cek['checks'])->keyBy('key');
        $this->assertSame('lolos', $checks['tanda_tangan_digital']['status']);
        $this->assertSame('gagal', $checks['penerbit']['status']);
        $this->assertSame('dilewati', $checks['nama_pelaku_usaha']['status']);
    }

    public function test_update_auto_verifies_when_all_checks_pass(): void
    {
        $user = $this->makeUser('perusahaan', 'PT Sylva Restu Abadi');
        $komoditas = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'komoditas-entitas/old.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Rejected,
        ]);

        $pdf = $this->buildSertifikatPdf($this->validSertifikatLines(
            'PT Sylva Restu Abadi', 'Mamalia', 'STD-004/OSS/2026', '15 Maret 2026'
        ));

        $this->actingAs($user)->put(route('komoditas-entitas.update', $record), [
            'komoditas_id' => $komoditas->id,
            'nomor_sertifikat' => 'STD-004/OSS/2026',
            'tanggal_terbit' => '2026-03-15',
            'file' => UploadedFile::fake()->createWithContent('baru.pdf', $pdf),
        ])->assertRedirect(route('komoditas-entitas.index'));

        $record->refresh();
        $this->assertSame(KomoditasEntitasStatus::Verified, $record->status);
        $this->assertTrue($record->summary_cek['valid']);
    }

    public function test_verifikasi_fails_gracefully_when_file_is_missing_from_storage(): void
    {
        $komoditas = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);
        $entitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Test', 'address' => 'Jl. Test']);

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $entitas->id,
            'file_path' => 'komoditas-entitas/tidak-ada.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        app(KomoditasEntitasAutoVerifikasiService::class)->verifikasi($record);

        $record->refresh();
        $this->assertSame(KomoditasEntitasStatus::Pending, $record->status);
        $this->assertFalse($record->summary_cek['valid']);
        $this->assertSame('dokumen', $record->summary_cek['checks'][0]['key']);
    }
}
