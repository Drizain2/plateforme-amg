<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;

return array_filter([
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    // laravel/telescope est en require-dev : absent d'une install --no-dev
    // (build Docker, déploiement), donc on ne l'enregistre que s'il est présent.
    class_exists(TelescopeServiceProvider::class) ? App\Providers\TelescopeServiceProvider::class : null,
]);
