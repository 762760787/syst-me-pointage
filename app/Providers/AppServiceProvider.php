<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// CORRECTION : Ajout de la façade URL
use Illuminate\Support\Facades\URL;

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
        // CORRECTION : On force toutes les URLs à être générées en HTTPS.
        // C'est la solution pour les environnements derrière un proxy sécurisé comme ngrok.
        if ($this->app->environment('production') || $this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
