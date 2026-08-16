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
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $path = $request->file('file')->store('documents/'.$entityId, 'local');

        $document = Document::create([
            'entity_id' => $entityId,
            'created_by_id' => Auth::id(),
            'permohonan_id' => $request->validated('permohonan_id'),
            'nomor_document' => $request->validated('nomor_document'),
            'type_document' => $request->validated('type_document'),
            'issued_date' => $request->validated('issued_date'),
            'issued_country_id' => $request->validated('issued_country_id'),
            'path' => $path,
        ]);

        $document->satsLn()->sync($request->validated('sats_ln_ids') ?? []);

        return redirect()->route('documents.index')->with('success', __('Dokumen berhasil ditambahkan.'));
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorizeEntityAccess($document);

        $data = [
            'entity_id' => $request->validated('entity_id'),
            'permohonan_id' => $request->validated('permohonan_id'),
            'nomor_document' => $request->validated('nomor_document'),
            'type_document' => $request->validated('type_document'),
            'issued_date' => $request->validated('issued_date'),
            'issued_country_id' => $request->validated('issued_country_id'),
        ];

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($document->path);
            $data['path'] = $request->file('file')->store('documents/'.$data['entity_id'], 'local');
        }

        $document->update($data);
        $document->satsLn()->sync($request->validated('sats_ln_ids') ?? []);

        return redirect()->route('documents.index')->with('success', __('Dokumen berhasil diperbarui.'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorizeEntityAccess($document);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return redirect()->route('documents.index')->with('success', __('Dokumen berhasil dihapus.'));
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorizeEntityAccess($document);

        return Storage::disk('local')->download($document->path);
    }

    public function preview(Document $document): StreamedResponse
    {
        $this->authorizeEntityAccess($document);

        return Storage::disk('local')->response($document->path);
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
        $satsLn = $permohonan->satsLn()->get(['id', 'satsln_number_issued']);

        return response()->json($satsLn->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->satsln_number_issued,
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
}
