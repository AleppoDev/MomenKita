<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        /*
         | Kamera pelayar hanya wujud dalam konteks selamat. Kalau satu pautan
         | atau aset terjana sebagai http, tetamu boleh mendarat di halaman
         | tanpa kamera dan tidak tahu sebabnya.
         */
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
