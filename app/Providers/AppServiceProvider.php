<?php

namespace App\Providers;

use App\Events\PermohonanStatusChanged;
use App\Listeners\RecordPermohonanStatusActivity;
use App\Listeners\SendPermohonanStatusNotification;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Policies\KuotaPeriodPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PermohonanStatusChanged::class, SendPermohonanStatusNotification::class);
        Event::listen(PermohonanStatusChanged::class, RecordPermohonanStatusActivity::class);

        Gate::policy(KuotaPeriod::class, KuotaPeriodPolicy::class);
    }
}
