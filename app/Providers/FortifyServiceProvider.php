<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // On n'utilise Fortify que pour le 2FA ; on désactive ses routes
        // pour éviter tout conflit avec nos propres routes d'authentification.
        Fortify::ignoreRoutes();
    }
}
