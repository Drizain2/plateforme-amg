<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Gateways\ManualGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Passerelle active. Pour changer : remplacer ManualGateway par
        // PayDunyaGateway, WaveGateway, etc. Un seul endroit à modifier.
        $this->app->bind(PaymentGateway::class, ManualGateway::class);

        // Binding par nom de gateway pour le WebhookController.
        // Ajouter ici chaque nouvelle passerelle : 'payment.gateway.paydunya' => PayDunyaGateway::class
        $this->app->bind('payment.gateway.manual', ManualGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
