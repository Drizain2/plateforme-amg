<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Stock\CategorieController;
use App\Http\Controllers\Stock\DepotController;
use App\Http\Controllers\Stock\PartController;
use App\Http\Controllers\Stock\StockMovementController;
use App\Http\Controllers\Stock\SupplierController;
use App\Http\Middleware\EnsureTenantScope;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', redirect()->route('/login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', EnsureTenantScope::class])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('stock')->name('stock.')->group(function () {
        Route::resource('depots', DepotController::class)->except('show');
        Route::post('depots/{depot}/users', [DepotController::class, 'attachUser'])->name('depots.attach-user');
        Route::delete('depots/{depot}/users/{user}', [DepotController::class, 'detachUser'])->name('depots.detach-user');

        Route::resource('parts', PartController::class);
        Route::resource('categories', CategorieController::class)->except(['show', 'create', 'edit']);
        Route::resource('suppliers', SupplierController::class);

        Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index');
        Route::post('/movements', [StockMovementController::class, 'store'])->name('movements.store');
        Route::post('/movements/transfer', [StockMovementController::class, 'transfer'])->name('movements.transfer');
        Route::get('/alerts', [StockMovementController::class, 'alerts'])->name('alerts');
    });
});
