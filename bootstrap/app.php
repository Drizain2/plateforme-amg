<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->alias([
            'perm' => CheckPermission::class,
            'platform.admin' => EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Pages d'erreur Inertia pour 403 / 404 / 500 / 503 (prod uniquement)
        // $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
        //     if (
        //         ! config('app.debug') &&
        //         in_array($response->getStatusCode(), [403, 404, 500, 503], true) &&
        //         ! $request->is('api/*')
        //     ) {
        //         return Inertia::render('Errors/Error', ['status' => $response->getStatusCode()])
        //             ->toResponse($request)
        //             ->setStatusCode($response->getStatusCode());
        //     }
        // });
    })->create();
