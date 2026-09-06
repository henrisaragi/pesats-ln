<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Http\Requests\DocumentRequest;
use App\Models\Country;
use App\Models\Document;
use App\Models\Entitas\Entitas;
use App\Models\Permohonan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(): View
    {
        $viewer = Auth::user();
        $canViewAll = $viewer->can('documents.view-all');

        $documents = Document::query()
            ->with(['entitas', 'permohonan', 'issuedCountry', 'satsLn'])
            ->when(! $canViewAll, fn ($query) => $query->where('entity_id', $viewer->entity_id))
            ->latest()
            ->paginate(10);

        return view('documents.index', [
            'documents' => $documents,
            'entitasOptions' => $canViewAll
                ? Entitas::query()->orderBy('name')->get()
                : Entitas::query()->whereKey($viewer->entity_id)->get(),
            'documentTypeOptions' => DocumentType::cases(),
            'countryOptions' => Country::query()->orderBy('name')->get(),
            'indonesiaCountryId' => Country::query()->where('iso_code', 'ID')->value('id'),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $entityId = $request->validated('entity_id');
        $entitas = Entitas::findOrFail($entityId);
        $type = DocumentType::from($request->validated('type_document'));
        $file = $request->file('file');
        $key = $this->buildObjectKey($entitas, $type, $request->validated('nomor_document'), $file->extension() ?: 'pdf');

        Storage::disk('s3')->put($key, file_get_contents($file->getRealPath()));

        $document = Document::create([
            'entity_id' => $entityId,
            'created_by_id' => Auth::id(),
            'permohonan_id' => $request->validated('permohonan_id'),
            'nomor_document' => $request->validated('nomor_document'),
            'type_document' => $type,
            'issued_date' => $request->validated('issued_date'),
            'issued_country_id' => $request->validated('issued_country_id'),
            'path' => $key,
        ]);

        $document->satsLn()->sync($request->validated('sats_ln_ids') ?? []);

        return redirect()->route('documents.index')->with('success', __('Dokumen berhasil ditambahkan.'));
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorizeEntityAccess($document);

        $type = DocumentType::from($request->validated('type_document'));

        $data = [
            'entity_id' => $request->validated('entity_id'),
            'permohonan_id' => $request->validated('permohonan_id'),
            'nomor_document' => $request->validated('nomor_document'),
            'type_document' => $type,
            'issued_date' => $request->validated('issued_date'),
            'issued_country_id' => $request->validated('issued_country_id'),
        ];

        if ($request->hasFile('file')) {
            $entitas = Entitas::findOrFail($data['entity_id']);
            $file = $request->file('file');
            $newKey = $this->buildObjectKey($entitas, $type, $data['nomor_document'], $file->extension() ?: 'pdf');

            Storage::disk('s3')->put($newKey, file_get_contents($file->getRealPath()));
            Storage::disk('s3')->delete($document->path);

            $data['path'] = $newKey;
        }

        $document->update($data);
        $document->satsLn()->sync($request->validated('sats_ln_ids') ?? []);

        return redirect()->route('documents.index')->with('success', __('Dokumen berhasil diperbarui.'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorizeEntityAccess($document);

        Storage::disk('s3')->delete($document->path);
        $document->delete();

        return redirect()->route('documents.index')->with('success', __('Dokumen berhasil dihapus.'));
    }

    public function download(Document $document): RedirectResponse
    {
        $this->authorizeEntityAccess($document);

        $url = Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(10), [
            'ResponseContentDisposition' => 'attachment; filename="'.basename($document->path).'"',
        ]);

        return redirect($url);
    }

    public function preview(Document $document): RedirectResponse
    {
        $this->authorizeEntityAccess($document);

        $url = Storage::disk('s3')->temporaryUrl($document->path, now()->addMinutes(10));

        return redirect($url);
    }

    public function permohonanOptions(Entitas $entity): JsonResponse
    {
        $permohonan = Permohonan::query()
            ->where('entity_id', $entity->id)
            ->orderByDesc('id')
            ->get(['id', 'nomor_permohonan']);

        return response()->json($permohonan->map(fn (Permohonan $item) => [
            'id' => $item->id,
            'name' => $item->nomor_permohonan,
        ])->values());
    }

    public function satsLnOptions(Permohonan $permohonan): JsonResponse
    {
        $satsLn = $permohonan->satsLn()->get(['id', 'nomor_urut', 'satsln_number_issued']);

        return response()->json($satsLn->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->satsln_number_issued ?? '#'.$item->nomor_urut,
        ])->values());
    }

    /**
     * Tanpa permission documents.view-all, seorang user hanya boleh mengubah/menghapus/mengunduh
     * dokumen milik entitasnya sendiri - mencegah akses langsung lewat URL ke dokumen entitas lain
     * meski dokumen itu tidak muncul di daftar mereka.
     */
    private function authorizeEntityAccess(Document $document): void
    {
        $viewer = Auth::user();

        abort_unless($viewer->can('documents.view-all') || $viewer->entity_id === $document->entity_id, 403);
    }

    /**
     * Object key di bucket S3: folder {id}-{nama_entitas}/{tahun-bulan} supaya bisa ditelusuri
     * manual dari MinIO Console, lalu file bernama prefix tipe dokumen + nomor dokumen (slug) +
     * timestamp supaya tidak collision antar upload.
     */
    private function buildObjectKey(Entitas $entitas, DocumentType $type, string $nomorDocument, string $extension): string
    {
        $slugNomor = Str::slug($nomorDocument, '-') ?: 'dokumen';
        $filename = sprintf('%s_%s_%s.%s', $type->value, $slugNomor, now()->format('YmdHis'), $extension);
        $entityFolder = $entitas->id.'-'.(Str::slug($entitas->name, '_') ?: 'entitas');
        $monthFolder = now()->format('Y-m');

        return "documents/{$entityFolder}/{$monthFolder}/{$filename}";
    }
}
