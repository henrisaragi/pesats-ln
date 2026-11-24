<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FiturPanduan;
use App\Models\Panduan\TemplateSuratPernyataan;
use App\Models\Panduan\VideoPanduan;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanduanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);

        Storage::fake('s3');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeUser(array $permissions = []): User
    {
        $user = User::create([
            'name' => 'User Test',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function makeTemplateManager(): User
    {
        return $this->makeUser(['template-surat-pernyataan.view', 'template-surat-pernyataan.create', 'template-surat-pernyataan.update', 'template-surat-pernyataan.delete']);
    }

    private function makeVideoManager(): User
    {
        return $this->makeUser(['video-panduan.view', 'video-panduan.create', 'video-panduan.update', 'video-panduan.delete']);
    }

    private function makeTemplate(bool $isActive = true): TemplateSuratPernyataan
    {
        Storage::disk('s3')->put('panduan/template-surat-pernyataan/lama.pdf', 'pdf');

        return TemplateSuratPernyataan::create([
            'judul' => 'Surat Pernyataan Kebenaran Data',
            'file_path' => 'panduan/template-surat-pernyataan/lama.pdf',
            'original_filename' => 'lama.pdf',
            'fitur' => [FiturPanduan::AssesmentIsi],
            'is_active' => $isActive,
        ]);
    }

    public function test_user_without_permission_cannot_open_the_management_pages(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('template-surat-pernyataan.index'))->assertForbidden();
        $this->actingAs($user)->get(route('video-panduan.index'))->assertForbidden();
    }

    public function test_manager_can_create_a_template_assigned_to_isi_assesment(): void
    {
        $this->actingAs($this->makeTemplateManager())->post(route('template-surat-pernyataan.store'), [
            'judul' => 'Surat Pernyataan Kebenaran Data',
            'fitur' => [FiturPanduan::AssesmentIsi->value],
            'is_active' => '1',
            'file' => UploadedFile::fake()->create('pernyataan.docx', 100),
        ])->assertRedirect(route('template-surat-pernyataan.index'));

        $template = TemplateSuratPernyataan::sole();

        $this->assertTrue($template->isAssignedTo(FiturPanduan::AssesmentIsi));
        $this->assertTrue($template->is_active);
        $this->assertSame('pernyataan.docx', $template->original_filename);
        Storage::disk('s3')->assertExists($template->file_path);

        $this->get(route('template-surat-pernyataan.index'))->assertOk()->assertSee('Surat Pernyataan Kebenaran Data');
    }

    public function test_template_requires_fitur_and_a_document_file(): void
    {
        $this->actingAs($this->makeTemplateManager())->post(route('template-surat-pernyataan.store'), [
            'judul' => 'Template',
            'file' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors(['fitur', 'file']);

        $this->assertSame(0, TemplateSuratPernyataan::count());
    }

    public function test_updating_a_template_file_replaces_and_deletes_the_old_file(): void
    {
        $template = $this->makeTemplate();

        $this->actingAs($this->makeTemplateManager())->put(route('template-surat-pernyataan.update', $template), [
            'judul' => 'Judul Baru',
            'fitur' => [FiturPanduan::AssesmentIsi->value],
            'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('template-surat-pernyataan.index'));

        $template->refresh();

        $this->assertSame('Judul Baru', $template->judul);
        $this->assertFalse($template->is_active);
        Storage::disk('s3')->assertMissing('panduan/template-surat-pernyataan/lama.pdf');
        Storage::disk('s3')->assertExists($template->file_path);
    }

    public function test_destroying_a_template_deletes_its_file(): void
    {
        $template = $this->makeTemplate();

        $this->actingAs($this->makeTemplateManager())->delete(route('template-surat-pernyataan.destroy', $template))
            ->assertRedirect(route('template-surat-pernyataan.index'));

        $this->assertModelMissing($template);
        Storage::disk('s3')->assertMissing('panduan/template-surat-pernyataan/lama.pdf');
    }

    public function test_any_user_can_download_an_active_template_but_not_an_inactive_one(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('template-surat-pernyataan.download', $this->makeTemplate()))->assertRedirect();
        $this->actingAs($user)->get(route('template-surat-pernyataan.download', $this->makeTemplate(isActive: false)))->assertNotFound();
    }

    public function test_only_pdf_templates_can_be_previewed_inline(): void
    {
        $user = $this->makeUser();
        $docx = TemplateSuratPernyataan::create([
            'judul' => 'Template Word',
            'file_path' => 'panduan/template-surat-pernyataan/template.docx',
            'fitur' => [FiturPanduan::AssesmentIsi],
        ]);

        $this->actingAs($user)->get(route('template-surat-pernyataan.preview', $this->makeTemplate()))->assertRedirect();
        $this->actingAs($user)->get(route('template-surat-pernyataan.preview', $docx))->assertNotFound();
    }

    public function test_video_requires_a_link_or_an_uploaded_file(): void
    {
        $this->actingAs($this->makeVideoManager())->post(route('video-panduan.store'), [
            'judul' => 'Cara Mengisi Assesment',
            'fitur' => [FiturPanduan::AssesmentIsi->value],
        ])->assertSessionHasErrors(['video_url' => 'Isi link video atau upload file video.']);

        $this->assertSame(0, VideoPanduan::count());
    }

    public function test_switching_video_source_from_upload_to_link_deletes_the_uploaded_file(): void
    {
        $manager = $this->makeVideoManager();

        $this->actingAs($manager)->post(route('video-panduan.store'), [
            'judul' => 'Cara Mengisi Assesment',
            'fitur' => [FiturPanduan::AssesmentIsi->value],
            'is_active' => '1',
            'file' => UploadedFile::fake()->create('panduan.mp4', 500, 'video/mp4'),
        ])->assertRedirect(route('video-panduan.index'));

        $video = VideoPanduan::sole();
        $uploadedPath = $video->file_path;
        Storage::disk('s3')->assertExists($uploadedPath);

        $this->actingAs($manager)->put(route('video-panduan.update', $video), [
            'judul' => 'Cara Mengisi Assesment',
            'fitur' => [FiturPanduan::AssesmentIsi->value],
            'is_active' => '1',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertRedirect(route('video-panduan.index'));

        $video->refresh();

        $this->assertNull($video->file_path);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $video->youtubeEmbedUrl());
        Storage::disk('s3')->assertMissing($uploadedPath);
    }

    public function test_updating_video_without_new_source_keeps_the_uploaded_file(): void
    {
        Storage::disk('s3')->put('panduan/video-panduan/lama.mp4', 'mp4');
        $video = VideoPanduan::create([
            'judul' => 'Video',
            'file_path' => 'panduan/video-panduan/lama.mp4',
            'fitur' => [FiturPanduan::AssesmentIsi],
        ]);

        $this->actingAs($this->makeVideoManager())->put(route('video-panduan.update', $video), [
            'judul' => 'Video Diubah',
            'fitur' => [FiturPanduan::AssesmentIsi->value],
        ])->assertSessionHasNoErrors();

        $this->assertSame('panduan/video-panduan/lama.mp4', $video->refresh()->file_path);
        Storage::disk('s3')->assertExists('panduan/video-panduan/lama.mp4');
    }

    public function test_inactive_uploaded_video_cannot_be_streamed_by_regular_users(): void
    {
        $video = VideoPanduan::create([
            'judul' => 'Video',
            'file_path' => 'panduan/video-panduan/lama.mp4',
            'fitur' => [FiturPanduan::AssesmentIsi],
            'is_active' => false,
        ]);

        $this->actingAs($this->makeUser())->get(route('video-panduan.stream', $video))->assertNotFound();
        $this->actingAs($this->makeVideoManager())->get(route('video-panduan.stream', $video))->assertRedirect();
    }
}
