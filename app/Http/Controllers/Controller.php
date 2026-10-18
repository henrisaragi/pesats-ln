<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Tahun Aktif dari query "?year=" - disinkronkan dari localStorage "tahun_aktif" oleh
     * script di topbar untuk halaman bertanda data-active-year-page.
     */
    protected function activeYear(Request $request): int
    {
        return $request->filled('year') ? $request->integer('year') : now()->year;
    }
}
