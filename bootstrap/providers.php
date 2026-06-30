<?php

return array_filter([
    App\Providers\AppServiceProvider::class,
    // laravel/telescope est en require-dev : absent d'une install --no-dev
    // (build Docker, déploiement), donc on ne l'enregistre que s'il est présent.
    class_exists(Laravel\Telescope\TelescopeServiceProvider::class) ? App\Providers\TelescopeServiceProvider::class : null,
]);
