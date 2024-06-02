<?php

namespace Dipesh79\LaravelImePay;

use Illuminate\Support\ServiceProvider;

class LaravelImePayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/config/imepay.php' => config_path('imepay.php'),
        ]);
    }
}
