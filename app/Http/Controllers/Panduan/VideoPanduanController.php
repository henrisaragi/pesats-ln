<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panduan;

use App\Enums\FiturPanduan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panduan\VideoPanduanRequest;
use App\Models\Panduan\VideoPanduan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VideoPanduanController extends Controller
{
    public function index(Request $request): View
    {
        $items = VideoPanduan::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('judul', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('panduan.video-panduan.index', [
            'items' => $items,
            'fiturOptions' => FiturPanduan::cases(),
        ]);
    }

    public function store(VideoPanduanRequest $request): RedirectResponse
    {
        VideoPanduan::create([
            ...$request->safe()->except(['file', 'video_url']),
            ...$this->resolveSource($request),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('video-panduan.index')->with('success', __('Video panduan berhasil ditambahkan.'));
    }

    public function update(VideoPanduanRequest $request, VideoPanduan $video): RedirectResponse
    {
        $source = $this->resolveSource($request);
        $oldPath = $video->file_path;

        $video->update([...$request->safe()->except(['file', 'video_url']), ...$source]);

        if ($oldPath !== null && $video->file_path !== $oldPath) {
            Storage::disk('s3')->delete($oldPath);
        }

        return redirect()->route('video-panduan.index')->with('success', __('Video panduan berhasil diperbarui.'));
    }

    public function destroy(VideoPanduan $video): RedirectResponse
    {
        $video->delete();

        if ($video->file_path !== null) {
            Storage::disk('s3')->delete($video->file_path);
        }

        return redirect()->route('video-panduan.index')->with('success', __('Video panduan berhasil dihapus.'));
    }

    /**
     * Terbuka untuk semua user login (diputar dari fitur yang di-assign), video nonaktif hanya
     * untuk pengelola.
     */
    public function stream(VideoPanduan $video): RedirectResponse
    {
        abort_unless($video->file_path !== null, 404);
        abort_unless($video->is_active || Auth::user()->can('video-panduan.view'), 404);

        return redirect(Storage::disk('s3')->temporaryUrl($video->file_path, now()->addMinutes(60)));
    }

    /**
     * Satu sumber video per record: file baru menggantikan link, link baru menggantikan file;
     * tanpa keduanya (hanya saat ubah) sumber lama dipertahankan.
     *
     * @return array<string, string|null>
     */
    private function resolveSource(VideoPanduanRequest $request): array
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            return [
                'file_path' => $this->storeFile($file, $request->validated('judul')),
                'original_filename' => $file->getClientOriginalName(),
                'video_url' => null,
            ];
        }

        if ($request->filled('video_url')) {
            return [
                'video_url' => $request->validated('video_url'),
                'file_path' => null,
                'original_filename' => null,
            ];
        }

        return [];
    }

    private function storeFile(UploadedFile $file, string $judul): string
    {
        $filename = sprintf('%s_%s.%s', Str::slug($judul) ?: 'video', now()->format('YmdHis'), $file->extension() ?: 'mp4');
        $path = 'panduan/video-panduan/'.now()->format('Y-m').'/'.$filename;

        Storage::disk('s3')->putFileAs(dirname($path), $file, basename($path));

        return $path;
    }
}
