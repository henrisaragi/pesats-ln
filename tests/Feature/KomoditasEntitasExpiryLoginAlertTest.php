<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KomoditasEntitasStatus;
use App\Mail\OtpMail;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KomoditasEntitasExpiryLoginAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeUser(bool $subjectToMasaBerlaku = true): User
    {
        $entitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Test', 'address' => 'Jl. Test']);

        $user = User::create([
            'name' => 'Entity User',
            'email' => uniqid('entity', true).'@example.test',
            'username' => uniqid('entity', true),
            'password' => bcrypt('password'),
            'entity_id' => $entitas->id,
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
        $user->assignRole('perusahaan');

        if (! $subjectToMasaBerlaku) {
            Role::findByName('perusahaan', 'web')->revokePermissionTo('komoditas-entitas.masa-berlaku');
        }

        return $user;
    }

    private function makeKomoditasEntitas(User $user, string $tanggalBerakhir): KomoditasEntitas
    {
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);

        return KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'dummy.pdf',
            'tanggal_terbit' => '2020-01-01',
            'tanggal_berakhir' => $tanggalBerakhir,
            'status' => KomoditasEntitasStatus::Verified,
        ]);
    }

    /**
     * Login sekarang 2 langkah (kredensial lalu OTP, lihat claude/auth-task.md) - flash alert
     * kedaluwarsa komoditas baru terjadi setelah OTP diverifikasi, bukan lagi di POST /login.
     */
    private function completeOtpLogin(User $user, string $password = 'password'): TestResponse
    {
        Mail::fake();

        $this->post('/login', ['login' => $user->email, 'password' => $password]);

        $otpCode = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$otpCode) {
            $otpCode = $mail->otpCode;

            return true;
        });

        return $this->post('/login/otp', ['otp' => $otpCode]);
    }

    public function test_login_flashes_an_alert_when_komoditas_is_within_3_months_of_expiry(): void
    {
        $user = $this->makeUser();
        $this->makeKomoditasEntitas($user, now()->addMonths(2)->toDateString());

        $response = $this->completeOtpLogin($user);

        $response->assertSessionHas('komoditas_entitas_expiring_alert');
        $items = session('komoditas_entitas_expiring_alert');
        $this->assertSame('Reptil', $items[0]['komoditas']);
    }

    public function test_login_does_not_flash_an_alert_when_no_komoditas_is_nearing_expiry(): void
    {
        $user = $this->makeUser();
        $this->makeKomoditasEntitas($user, now()->addYear()->toDateString());

        $response = $this->completeOtpLogin($user);

        $response->assertSessionMissing('komoditas_entitas_expiring_alert');
    }

    public function test_login_does_not_flash_an_alert_for_entities_exempt_from_masa_berlaku(): void
    {
        $user = $this->makeUser(subjectToMasaBerlaku: false);
        $this->makeKomoditasEntitas($user, now()->addMonth()->toDateString());

        $response = $this->completeOtpLogin($user);

        $response->assertSessionMissing('komoditas_entitas_expiring_alert');
    }
}
