<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepotSwitchController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopUserController;
use App\Http\Controllers\Stock\CategorieController;
use App\Http\Controllers\Stock\DepotController;
use App\Http\Controllers\Stock\PartController;
use App\Http\Controllers\Stock\StockCountController;
use App\Http\Controllers\Stock\StockMovementController;
use App\Http\Controllers\Stock\SupplierController;
use App\Http\Controllers\Ticket\TicketController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Middleware\EnsureTenantScope;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/track/{token}', [TrackController::class, 'show'])->name('track');

Route::get('/invoices/{invoice}/pdf/public', [InvoiceController::class, 'publicPdf'])
    ->name('invoices.pdf.public')
    ->middleware('signed');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'store'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', EnsureTenantScope::class])->group(function () {
    // Sélection de dépôt (non-admins avec plusieurs dépôts)
    Route::get('/depot/select', [DepotSwitchController::class, 'select'])->name('depot.select');
    Route::post('/depot/select', [DepotSwitchController::class, 'save'])->name('depot.save');

    // Changement de dépôt actif (admin / super_admin uniquement)
    Route::post('/depot/switch', [DepotSwitchController::class, 'switch'])->name('depot.switch');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/cash', [ReportController::class, 'cash'])->name('reports.cash');

    Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::resource('customers', CustomerController::class)->except('create', 'edit');

    Route::prefix('stock')->name('stock.')->group(function () {
        Route::resource('depots', DepotController::class)->except('show');
        Route::post('depots/{depot}/users', [DepotController::class, 'attachUser'])->name('depots.attach-user');
        Route::delete('depots/{depot}/users/{user}', [DepotController::class, 'detachUser'])->name('depots.detach-user');

        Route::resource('parts', PartController::class)->except('show');
        Route::get('parts/search', [PartController::class, 'search'])->name('parts.search');
        Route::resource('categories', CategorieController::class)->except(['show', 'create', 'edit']);
        Route::resource('suppliers', SupplierController::class);

        Route::get('/movements', [StockMovementController::class, 'index'])->name('movements.index');
        Route::post('/movements', [StockMovementController::class, 'store'])->name('movements.store');
        Route::post('/movements/transfer', [StockMovementController::class, 'transfer'])->name('movements.transfer');
        Route::get('/alerts', [StockMovementController::class, 'alerts'])->name('alerts');

        Route::prefix('counts')->name('counts.')->group(function () {
            Route::get('/', [StockCountController::class, 'index'])->name('index');
            Route::post('/', [StockCountController::class, 'store'])->name('store');
            Route::get('/{stockCount}', [StockCountController::class, 'show'])->name('show');
            Route::put('/{stockCount}', [StockCountController::class, 'update'])->name('update');
            Route::post('/{stockCount}/validate', [StockCountController::class, 'validateCount'])->name('validate');
        });
    });

    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [TicketController::class, 'update'])->name('update');
        Route::post('/{ticket}/transition', [TicketController::class, 'transition'])->name('transition');
        Route::post('/{ticket}/notes', [TicketController::class, 'addNote'])->name('notes.store');
        Route::post('/{ticket}/parts', [TicketController::class, 'consumePart'])->name('parts.store');
        Route::post('/{ticket}/invoice', [InvoiceController::class, 'fromTicket'])->name('invoice');
    });

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::put('/{invoice}', [InvoiceController::class, 'update'])->name('update');
        Route::post('/{invoice}/transition', [InvoiceController::class, 'transition'])->name('transition');
        Route::post('/{invoice}/lines', [InvoiceController::class, 'storeLine'])->name('lines.store');
        Route::delete('/{invoice}/lines/{line}', [InvoiceController::class, 'destroyLine'])->name('lines.destroy');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
    });

    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseController::class, 'create'])->name('create');
        Route::post('/', [PurchaseController::class, 'store'])->name('store');
        Route::get('/{purchase}', [PurchaseController::class, 'show'])->name('show');
        Route::post('/{purchase}/transition', [PurchaseController::class, 'transition'])->name('transition');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [ShopUserController::class, 'index'])->name('index');
        Route::post('/', [ShopUserController::class, 'store'])->name('store');
        Route::put('/{user}', [ShopUserController::class, 'update'])->name('update');
        Route::delete('/{user}', [ShopUserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/toggle-active', [ShopUserController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{user}/reset-password', [ShopUserController::class, 'resetPassword'])->name('reset-password');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'edit'])->name('edit');
        Route::post('/shop', [SettingsController::class, 'updateShop'])->name('shop');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password');
    });

    Route::prefix('users/{user}/permissions')->name('users.permissions.')->group(function () {
        Route::get('/', [UserPermissionController::class, 'index'])->name('index');
        Route::post('/', [UserPermissionController::class, 'update'])->name('update');
        Route::delete('/', [UserPermissionController::class, 'resetAll'])->name('reset');
    });

});
