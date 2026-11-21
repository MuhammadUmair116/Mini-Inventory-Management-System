<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('welcome');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'role:admin,staff'])
    ->name('dashboard');

Route::middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:admin,staff'])->group(function () {
    Route::resource('products', ProductController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middlewareFor(['store', 'update', 'destroy'], 'role:admin');

    Route::resource('customers', CustomerController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->middlewareFor(['store', 'update', 'destroy'], 'role:admin');

    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');

    Route::resource('payments', PaymentController::class)
        ->only(['index', 'store'])
        ->middlewareFor(['store'], 'role:admin');

    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('units', UnitController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('users', UserController::class)->only(['index', 'store', 'update']);

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
