<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\{
    HomeController,
    ProductController,
    CartController,
    CheckoutController,
    OrderController,
    AddressController
};
use App\Http\Controllers\Admin\{
    DashboardController,
    ProductController as AdminProductController,
    OrderController as AdminOrderController,
    CourierController,
    RouteController,
    ReportController,
    CustomerController
};
use Illuminate\Support\Facades\Route;

// ===== BREEZE DEFAULT (profile settings, dashboard bawaan) =====
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/dashboard', function () {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('user.home');
    })->middleware(['auth', 'verified'])->name('dashboard');
});

require __DIR__ . '/auth.php';

// ===== USER (public, tidak wajib login) =====
Route::name('user.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::put('/cart/{productId}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{productId}', [CartController::class, 'destroy'])->name('cart.destroy');
});

// ===== USER (wajib login) =====
Route::middleware('auth')->name('user.')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
});

// ===== ADMIN (wajib login + role admin) =====
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', AdminProductController::class);

    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    Route::post('/orders/{order}/assign-courier', [AdminOrderController::class, 'assignCourier'])->name('orders.assign-courier');

    Route::resource('couriers', CourierController::class)->except(['show']);

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::get('/routes/optimize', [RouteController::class, 'optimize'])->name('routes.optimize');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});