<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

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
        // Timezone Indonesia
        date_default_timezone_set('Asia/Jakarta');
        Carbon::setLocale('id');

        // Force HTTPS (WAJIB karena pakai Apache Proxy + SSL)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}