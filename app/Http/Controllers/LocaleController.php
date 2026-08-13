<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    private const SUPPORTED_LOCALES = ['id', 'en'];

    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, self::SUPPORTED_LOCALES, true), 404);

        $request->session()->put('locale', $locale);

        return redirect()->back();
    }
}
