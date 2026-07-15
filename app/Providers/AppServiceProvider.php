<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Gateways\ManualGateway;
use App\Gateways\PayDunyaGateway;
use App\Gateways\WaveGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Passerelle active, choisie via PAYMENT_GATEWAY dans .env.
        $this->app->bind(PaymentGateway::class, fn () => match (config('payment.gateway', 'manual')) {
            'paydunya' => $this->app->make(PayDunyaGateway::class),
            'wave' => $this->app->make(WaveGateway::class),
            default => $this->app->make(ManualGateway::class),
        });

        // Bindings nommés pour le WebhookController (indépendants de la gateway active).
        $this->app->bind('payment.gateway.manual', ManualGateway::class);
        $this->app->bind('payment.gateway.paydunya', PayDunyaGateway::class);
        $this->app->bind('payment.gateway.wave', WaveGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
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
