<?php

declare(strict_types=1);

namespace App\Http\Controllers\Assesment;

use App\Http\Controllers\Controller;
use App\Models\Assesment\AssesmentAspek;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssesmentAspekController extends Controller
{
    /**
     * Typeahead pencarian Aspek/Indikator Penilaian (>= 3 huruf) - lihat
     * resources/js/pages/assesment-pertanyaan-form.js. Master data global, tidak dibatasi per
     * judul assesment.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        if (mb_strlen($query) < 3) {
            return response()->json([]);
        }

        // whereRaw lower() dipakai (bukan operator "ilike") supaya portable di SQLite (test suite)
        // dan Postgres (production) - sama seperti pencarian aspek exact-match di
        // AssesmentPertanyaanController.
        $items = AssesmentAspek::query()
            ->whereRaw('lower(nama) like ?', ['%'.Str::lower($query).'%'])
            ->orderBy('nama')
            ->limit(10)
            ->get(['id', 'nama']);

        return response()->json($items);
    }
}
