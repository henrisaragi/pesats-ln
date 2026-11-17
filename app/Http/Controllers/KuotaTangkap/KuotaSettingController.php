<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaTangkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\KuotaTangkap\KuotaSettingRequest;
use App\Models\KuotaTangkap\KuotaActivity;
use App\Models\KuotaTangkap\KuotaSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KuotaSettingController extends Controller
{
    public function edit(): View
    {
        return view('kuota-tangkap.settings', [
            'setting' => KuotaSetting::current()->load('updatedBy'),
            'activities' => KuotaActivity::query()->whereNull('kuota_period_id')->where('action', 'settings_updated')->with('user')->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(KuotaSettingRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $setting = KuotaSetting::current();
            $old = $setting->only(['bobot_ekspor', 'bobot_evaluasi', 'cadangan']);

            $setting->update([...$request->fractions(), 'updated_by' => $request->user()->id]);

            KuotaActivity::record(null, 'settings_updated', $request->user(), $old, $setting->only(['bobot_ekspor', 'bobot_evaluasi', 'cadangan']));
        });

        return redirect()->route('kuota-tangkap.settings.edit')->with('success', __('Pengaturan kuota ekspor berhasil disimpan.'));
    }
}
