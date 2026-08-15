<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KomoditasEntitasStatus;
use App\Enums\NotificationCategory;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\User;
use App\Notifications\AppNotification;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KomoditasEntitasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);

        Storage::fake('local');
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

    public function test_perusahaan_user_can_view_index(): void
    {
        $user = $this->makeUser('perusahaan');

        $this->actingAs($user)->get(route('komoditas-entitas.index'))->assertOk();
    }

    public function test_perorangan_user_cannot_view_index(): void
    {
        $user = $this->makeUser('perorangan');

        $this->actingAs($user)->get(route('komoditas-entitas.index'))->assertForbidden();
    }

    public function test_perusahaan_user_can_submit_komoditas_entitas(): void
    {
        $user = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        $response = $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-01-10',
            'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('komoditas-entitas.index'));

        $this->assertDatabaseHas('komoditas_entitas', [
            'entitas_id' => $user->entity_id,
            'komoditas_id' => $komoditas->id,
            'status' => KomoditasEntitasStatus::Pending->value,
        ]);

        $record = KomoditasEntitas::where('entitas_id', $user->entity_id)->firstOrFail();
        $this->assertSame('2026-01-10', $record->tanggal_terbit->toDateString());
        $this->assertSame('2031-01-10', $record->tanggal_berakhir->toDateString());
        Storage::disk('local')->assertExists($record->file_path);
    }

    public function test_submitting_notifies_users_with_verify_permission_only(): void
    {
        Notification::fake();

        $submitter = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('komoditas-entitas.verify');

        $bystander = $this->makeUser('perusahaan');

        $this->actingAs($submitter)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-01-10',
            'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('komoditas-entitas.index'));

        Notification::assertSentTo(
            $verifier,
            AppNotification::class,
            fn (AppNotification $notification) => $notification->category === NotificationCategory::Permohonan
                && $notification->url === route('komoditas-entitas.verifikasi.index')
        );

        Notification::assertNotSentTo($bystander, AppNotification::class);
        Notification::assertNotSentTo($submitter, AppNotification::class);
    }

    public function test_non_pdf_file_is_rejected(): void
    {
        $user = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        $response = $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-01-10',
            'file' => UploadedFile::fake()->create('dokumen.txt', 100, 'text/plain'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('komoditas_entitas', ['entitas_id' => $user->entity_id]);
    }

    public function test_perorangan_user_cannot_submit(): void
    {
        $user = $this->makeUser('perorangan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-01-10',
            'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_verifier_can_verify_and_reject_submissions(): void
    {
        $submitter = $this->makeUser('penangkaran');
        $verifier = $this->makeUser('perusahaan');
        $verifier->givePermissionTo('komoditas-entitas.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        $toVerify = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $submitter->entity_id,
            'file_path' => 'komoditas-entitas/dummy1.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $toReject = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $submitter->entity_id,
            'file_path' => 'komoditas-entitas/dummy2.pdf',
            'tanggal_terbit' => '2026-02-01',
            'tanggal_berakhir' => '2031-02-01',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $this->actingAs($verifier)->get(route('komoditas-entitas.verifikasi.index'))->assertOk();

        $this->actingAs($verifier)
            ->post(route('komoditas-entitas.verifikasi.verify', $toVerify))
            ->assertRedirect(route('komoditas-entitas.verifikasi.index'));

        $this->actingAs($verifier)
            ->post(route('komoditas-entitas.verifikasi.reject', $toReject))
            ->assertRedirect(route('komoditas-entitas.verifikasi.index'));

        $toVerify->refresh();
        $toReject->refresh();

        $this->assertSame(KomoditasEntitasStatus::Verified, $toVerify->status);
        $this->assertSame($verifier->id, $toVerify->verified_by);
        $this->assertNotNull($toVerify->verified_at);

        $this->assertSame(KomoditasEntitasStatus::Rejected, $toReject->status);
    }

    public function test_verify_and_reject_notify_the_submitting_entitys_users(): void
    {
        Notification::fake();

        $submitter = $this->makeUser('penangkaran');
        $verifier = $this->makeUser('perusahaan');
        $verifier->givePermissionTo('komoditas-entitas.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        $toVerify = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $submitter->entity_id,
            'file_path' => 'komoditas-entitas/dummy1.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $toReject = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $submitter->entity_id,
            'file_path' => 'komoditas-entitas/dummy2.pdf',
            'tanggal_terbit' => '2026-02-01',
            'tanggal_berakhir' => '2031-02-01',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $this->actingAs($verifier)->post(route('komoditas-entitas.verifikasi.verify', $toVerify));
        $this->actingAs($verifier)->post(route('komoditas-entitas.verifikasi.reject', $toReject));

        Notification::assertSentToTimes($submitter, AppNotification::class, 2);

        Notification::assertSentTo(
            $submitter,
            AppNotification::class,
            fn (AppNotification $notification) => $notification->category === NotificationCategory::Permohonan
                && $notification->title === __('Pengajuan Komoditas Entitas Terverifikasi')
        );

        Notification::assertSentTo(
            $submitter,
            AppNotification::class,
            fn (AppNotification $notification) => $notification->category === NotificationCategory::Permohonan
                && $notification->title === __('Pengajuan Komoditas Entitas Ditolak')
        );

        Notification::assertNotSentTo($verifier, AppNotification::class);
    }

    public function test_download_is_restricted_to_owner_and_verifier(): void
    {
        $submitter = $this->makeUser('perusahaan');
        $verifier = $this->makeUser('penangkaran');
        $verifier->givePermissionTo('komoditas-entitas.verify');
        $stranger = $this->makeUser('perusahaan');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        Storage::disk('local')->put('komoditas-entitas/dummy.pdf', 'fake-pdf-content');

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $submitter->entity_id,
            'file_path' => 'komoditas-entitas/dummy.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $this->actingAs($submitter)->get(route('komoditas-entitas.download', $record))->assertOk();
        $this->actingAs($verifier)->get(route('komoditas-entitas.download', $record))->assertOk();
        $this->actingAs($stranger)->get(route('komoditas-entitas.download', $record))->assertForbidden();

        $this->actingAs($submitter)->get(route('komoditas-entitas.preview', $record))->assertOk();
        $this->actingAs($verifier)->get(route('komoditas-entitas.preview', $record))->assertOk();
        $this->actingAs($stranger)->get(route('komoditas-entitas.preview', $record))->assertForbidden();
    }

    public function test_verifier_without_view_permission_can_still_access_document(): void
    {
        $submitter = $this->makeUser('perusahaan');

        // A verifier role assigned *only* komoditas-entitas.verify (no .view) - the route
        // middleware must accept either permission, since the controller already allows
        // verifiers regardless of .view.
        $verifier = User::create([
            'name' => 'Verifier Only',
            'email' => uniqid('verifier', true).'@example.test',
            'username' => uniqid('verifier', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
        $verifier->givePermissionTo('komoditas-entitas.verify');

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        Storage::disk('local')->put('komoditas-entitas/dummy.pdf', 'fake-pdf-content');

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $submitter->entity_id,
            'file_path' => 'komoditas-entitas/dummy.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $this->actingAs($verifier)->get(route('komoditas-entitas.preview', $record))->assertOk();
        $this->actingAs($verifier)->get(route('komoditas-entitas.download', $record))->assertOk();
    }

    public function test_cannot_submit_the_same_komoditas_twice_while_pending_or_verified(): void
    {
        $user = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'komoditas-entitas/existing.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $response = $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-02-01',
            'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('komoditas_id');
        $this->assertSame(1, KomoditasEntitas::where('entitas_id', $user->entity_id)->count());
    }

    public function test_can_submit_a_different_komoditas_while_one_is_pending(): void
    {
        $user = $this->makeUser('perusahaan');
        $reptil = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        $mamalia = Komoditas::create(['name' => 'Mamalia', 'code' => 'MML']);

        KomoditasEntitas::create([
            'komoditas_id' => $reptil->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'komoditas-entitas/existing.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Pending,
        ]);

        $this->actingAs($user)->post(route('komoditas-entitas.store'), [
            'komoditas_id' => $mamalia->id,
            'tanggal_terbit' => '2026-02-01',
            'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('komoditas-entitas.index'));

        $this->assertSame(2, KomoditasEntitas::where('entitas_id', $user->entity_id)->count());
    }

    public function test_rejected_submission_can_be_edited_and_resets_to_pending(): void
    {
        $user = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        Storage::disk('local')->put('komoditas-entitas/old.pdf', 'old-content');

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'komoditas-entitas/old.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Rejected,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->put(route('komoditas-entitas.update', $record), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-03-05',
            'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect(route('komoditas-entitas.index'));

        $record->refresh();
        $this->assertSame(KomoditasEntitasStatus::Pending, $record->status);
        $this->assertNull($record->verified_by);
        $this->assertNull($record->verified_at);
        $this->assertSame('2026-03-05', $record->tanggal_terbit->toDateString());
        $this->assertSame('2031-03-05', $record->tanggal_berakhir->toDateString());
        Storage::disk('local')->assertExists($record->file_path);
        Storage::disk('local')->assertMissing('komoditas-entitas/old.pdf');
    }

    public function test_editing_a_rejected_submission_without_a_new_file_keeps_the_existing_one(): void
    {
        $user = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        Storage::disk('local')->put('komoditas-entitas/keep.pdf', 'keep-content');

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'komoditas-entitas/keep.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Rejected,
        ]);

        $this->actingAs($user)->put(route('komoditas-entitas.update', $record), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-03-05',
        ])->assertRedirect(route('komoditas-entitas.index'));

        $record->refresh();
        $this->assertSame('komoditas-entitas/keep.pdf', $record->file_path);
        Storage::disk('local')->assertExists('komoditas-entitas/keep.pdf');
    }

    public function test_pending_or_verified_submission_cannot_be_edited_or_deleted(): void
    {
        $user = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'komoditas-entitas/dummy.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Verified,
        ]);

        $this->actingAs($user)->put(route('komoditas-entitas.update', $record), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-03-05',
        ])->assertForbidden();

        $this->actingAs($user)->delete(route('komoditas-entitas.destroy', $record))->assertForbidden();

        $this->assertDatabaseHas('komoditas_entitas', ['id' => $record->id, 'status' => KomoditasEntitasStatus::Verified->value]);
    }

    public function test_rejected_submission_can_be_deleted(): void
    {
        $user = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);
        Storage::disk('local')->put('komoditas-entitas/dummy.pdf', 'fake-pdf-content');

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $user->entity_id,
            'file_path' => 'komoditas-entitas/dummy.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Rejected,
        ]);

        $this->actingAs($user)->delete(route('komoditas-entitas.destroy', $record))
            ->assertRedirect(route('komoditas-entitas.index'));

        $this->assertDatabaseMissing('komoditas_entitas', ['id' => $record->id]);
        Storage::disk('local')->assertMissing('komoditas-entitas/dummy.pdf');
    }

    public function test_cannot_edit_or_delete_another_entitys_submission(): void
    {
        $owner = $this->makeUser('perusahaan');
        $stranger = $this->makeUser('perusahaan');
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT']);

        $record = KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $owner->entity_id,
            'file_path' => 'komoditas-entitas/dummy.pdf',
            'tanggal_terbit' => '2026-01-10',
            'tanggal_berakhir' => '2031-01-10',
            'status' => KomoditasEntitasStatus::Rejected,
        ]);

        $this->actingAs($stranger)->put(route('komoditas-entitas.update', $record), [
            'komoditas_id' => $komoditas->id,
            'tanggal_terbit' => '2026-03-05',
        ])->assertForbidden();

        $this->actingAs($stranger)->delete(route('komoditas-entitas.destroy', $record))->assertForbidden();
    }
}
