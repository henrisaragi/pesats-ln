<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppSettingController extends Controller
{
    public function index(): View
    {
        return view('settings.whatsapp.index', [
            'isEnabled' => WhatsAppSetting::isEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        WhatsAppSetting::setEnabled($request->boolean('is_enabled'));

        return redirect()->route('settings.whatsapp.index')
            ->with('success', __('Pengaturan notifikasi WhatsApp berhasil disimpan.'));
    }
}
