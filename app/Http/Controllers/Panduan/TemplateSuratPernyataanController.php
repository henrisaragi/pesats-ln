<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panduan;

use App\Enums\FiturPanduan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panduan\TemplateSuratPernyataanRequest;
use App\Models\Panduan\TemplateSuratPernyataan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TemplateSuratPernyataanController extends Controller
{
    public function index(Request $request): View
    {
        $items = TemplateSuratPernyataan::query()
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('judul', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('panduan.template-surat-pernyataan.index', [
            'items' => $items,
            'fiturOptions' => FiturPanduan::cases(),
        ]);
    }

    public function store(TemplateSuratPernyataanRequest $request): RedirectResponse
    {
        $file = $request->file('file');

        TemplateSuratPernyataan::create([
            ...$request->safe()->except('file'),
            'file_path' => $this->storeFile($file, $request->validated('judul')),
            'original_filename' => $file->getClientOriginalName(),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('template-surat-pernyataan.index')
            ->with('success', __('Template surat pernyataan berhasil ditambahkan.'));
    }

    public function update(TemplateSuratPernyataanRequest $request, TemplateSuratPernyataan $template): RedirectResponse
    {
        $data = $request->safe()->except('file');
        $oldPath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $oldPath = $template->file_path;
            $data['file_path'] = $this->storeFile($file, $data['judul']);
            $data['original_filename'] = $file->getClientOriginalName();
        }

        $template->update($data);

        if ($oldPath !== null) {
            Storage::disk('s3')->delete($oldPath);
        }

        return redirect()->route('template-surat-pernyataan.index')
            ->with('success', __('Template surat pernyataan berhasil diperbarui.'));
    }

    public function destroy(TemplateSuratPernyataan $template): RedirectResponse
    {
        $template->delete();
        Storage::disk('s3')->delete($template->file_path);

        return redirect()->route('template-surat-pernyataan.index')
            ->with('success', __('Template surat pernyataan berhasil dihapus.'));
    }

    /**
     * Terbuka untuk semua user login (template diunduh entitas dari fitur yang di-assign), tapi
     * template nonaktif hanya bisa diunduh pengelola.
     */
    public function download(TemplateSuratPernyataan $template): RedirectResponse
    {
        $this->abortUnlessAccessible($template);

        $filename = $template->original_filename ?: basename($template->file_path);

        $url = Storage::disk('s3')->temporaryUrl($template->file_path, now()->addMinutes(10), [
            'ResponseContentDisposition' => 'attachment; filename="'.addcslashes($filename, '"\\').'"',
        ]);

        return redirect($url);
    }

    /**
     * Pratinjau inline untuk viewer modal fullscreen (x-document-viewer-modal) - hanya PDF.
     */
    public function preview(TemplateSuratPernyataan $template): RedirectResponse
    {
        $this->abortUnlessAccessible($template);

        abort_unless($template->isPreviewable(), 404);

        return redirect(Storage::disk('s3')->temporaryUrl($template->file_path, now()->addMinutes(10)));
    }

    private function abortUnlessAccessible(TemplateSuratPernyataan $template): void
    {
        abort_unless($template->is_active || Auth::user()->can('template-surat-pernyataan.view'), 404);
    }

    private function storeFile(UploadedFile $file, string $judul): string
    {
        $filename = sprintf('%s_%s.%s', Str::slug($judul) ?: 'template', now()->format('YmdHis'), $file->extension() ?: 'pdf');
        $path = 'panduan/template-surat-pernyataan/'.now()->format('Y-m').'/'.$filename;

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }
}
