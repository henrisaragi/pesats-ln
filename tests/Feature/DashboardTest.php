<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AssesmentIsiStatus;
use App\Enums\KomoditasEntitasStatus;
use App\Models\Assesment\Assesment;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeUser(?string $entityType = 'perusahaan'): User
    {
        $entity = $entityType === null ? null : Entitas::create([
            'entity_type' => $entityType,
            'name' => 'Entitas '.uniqid(),
            'address' => 'Jl. Test',
        ]);

        $user = User::create([
            'name' => 'Budi Dashboard',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'entity_id' => $entity?->id,
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        if ($entityType !== null) {
            $user->assignRole($entityType);
        }

        return $user;
    }

    private function makeKomoditasEntitas(User $owner, KomoditasEntitasStatus $status): KomoditasEntitas
    {
        return KomoditasEntitas::create([
            'komoditas_id' => Komoditas::create(['name' => 'Komoditas '.uniqid(), 'code' => uniqid()])->id,
            'entitas_id' => $owner->entity_id,
            'file_path' => 'dummy.pdf',
            'tanggal_terbit' => '2026-01-01',
            'tanggal_berakhir' => '2031-01-01',
            'status' => $status,
        ]);
    }

    /**
     * @param  array{route: string, total: int, statuses: list<array{status: \BackedEnum, total: int}>}|null  $summary
     * @return array<string, int>
     */
    private function totalsByStatus(?array $summary): array
    {
        return collect($summary['statuses'])->mapWithKeys(fn (array $row): array => [$row['status']->value => $row['total']])->all();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.index'))->assertRedirect(route('login'));
    }

    public function test_dashboard_welcomes_the_user_and_shows_the_active_year(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/dashboards?year=2025')
            ->assertOk()
            ->assertSee('Beranda')
            ->assertSee('Selamat datang, Budi Dashboard')
            ->assertSeeInOrder(['Akses Tahun:', '2025'])
            ->assertSee('Take Tour');
    }

    public function test_sidebar_starts_with_home_section_and_its_dashboard_tour_step(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('dashboards.index'))
            ->assertSeeInOrder(['Home', 'data-tour-title="Dashboard"', route('dashboards.index')], false);
    }

    public function test_status_cards_only_count_submissions_of_the_active_year(): void
    {
        $owner = $this->makeUser();
        $this->makeKomoditasEntitas($owner, KomoditasEntitasStatus::Pending)
            ->forceFill(['created_at' => '2025-06-01 10:00:00'])->save();
        $this->makeKomoditasEntitas($owner, KomoditasEntitasStatus::Verified);

        $this->actingAs($owner)->get(route('dashboards.index', ['year' => 2025]))
            ->assertViewHas('year', 2025)
            ->assertViewHas('komoditasSummary', fn (?array $summary): bool => $summary['total'] === 1
                && $this->totalsByStatus($summary)['pending'] === 1);
    }

    public function test_old_dashboard_url_and_root_redirect_to_dashboards(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/dashboards/index')->assertRedirect(route('dashboards.index'));
        $this->actingAs($user)->get('/')->assertRedirect(route('dashboards.index'));
    }

    public function test_entity_owner_sees_only_its_own_komoditas_statuses(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $this->makeKomoditasEntitas($owner, KomoditasEntitasStatus::Pending);
        $this->makeKomoditasEntitas($owner, KomoditasEntitasStatus::Verified);
        $this->makeKomoditasEntitas($owner, KomoditasEntitasStatus::Verified);
        $this->makeKomoditasEntitas($other, KomoditasEntitasStatus::Rejected);

        $this->actingAs($owner)->get(route('dashboards.index'))
            ->assertViewHas('komoditasSummary', fn (?array $summary): bool => $summary['route'] === 'komoditas-entitas.index'
                && $summary['total'] === 3
                && $this->totalsByStatus($summary) === ['pending' => 1, 'verified' => 2, 'rejected' => 0, 'expired' => 0]);
    }

    public function test_verifier_sees_komoditas_statuses_of_all_entities(): void
    {
        $verifier = $this->makeUser(null);
        $verifier->givePermissionTo('komoditas-entitas.verify');
        $this->makeKomoditasEntitas($this->makeUser(), KomoditasEntitasStatus::Pending);
        $this->makeKomoditasEntitas($this->makeUser(), KomoditasEntitasStatus::Rejected);

        $this->actingAs($verifier)->get(route('dashboards.index'))
            ->assertViewHas('komoditasSummary', fn (?array $summary): bool => $summary['route'] === 'komoditas-entitas.verifikasi.index'
                && $this->totalsByStatus($summary) === ['pending' => 1, 'verified' => 0, 'rejected' => 1, 'expired' => 0])
            ->assertViewHas('assesmentSummary', null);
    }

    public function test_entity_owner_sees_only_its_own_assesment_statuses(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $assesment = Assesment::create(['judul' => 'Assesment Test']);
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        foreach ([[$owner, AssesmentIsiStatus::Submitted], [$owner, AssesmentIsiStatus::Draft], [$other, AssesmentIsiStatus::Verified]] as [$user, $status]) {
            AssesmentIsi::create([
                'assesment_id' => $assesment->id,
                'entitas_id' => $user->entity_id,
                'komoditas_id' => $komoditas->id,
                'status' => $status,
            ]);
        }

        $this->actingAs($owner)->get(route('dashboards.index'))
            ->assertViewHas('assesmentSummary', fn (?array $summary): bool => $summary['route'] === 'assesment-isi.index'
                && $this->totalsByStatus($summary) === ['draft' => 1, 'submitted' => 1, 'verified' => 0]);
    }

    public function test_user_without_related_permissions_sees_no_status_cards(): void
    {
        $user = $this->makeUser(null);

        $this->actingAs($user)->get(route('dashboards.index'))
            ->assertOk()
            ->assertViewHas('komoditasSummary', null)
            ->assertViewHas('assesmentSummary', null)
            ->assertDontSee('Status Verifikasi Komoditas');
    }
}
