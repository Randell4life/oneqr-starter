<?php

use App\Http\Controllers\BusinessManagementController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\SmartPageController;
use App\Http\Controllers\BusinessCatalogController;
use App\Http\Controllers\BusinessCartController;
use App\Http\Controllers\BusinessInventoryController;
use App\Http\Controllers\BusinessOrderPaymentController;
use App\Http\Controllers\BusinessRegistrationController;
use App\Http\Controllers\BusinessQrController;
use App\Http\Controllers\BusinessSubscriptionController;
use App\Http\Controllers\BusinessSubscriptionPaymentController;
use App\Http\Controllers\QrAnalyticsController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\QrImageController;
use App\Http\Controllers\QrRedirectController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', BusinessRegistrationController::class)->name('register.store');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
});

Route::post('/payments/tbc/callback', [BusinessSubscriptionPaymentController::class, 'callback'])->middleware('throttle:60,1')->name('payments.tbc.callback');


Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $businesses = $user->businesses()->withCount('qrCodes')->latest()->get();
        $teamMemberships = $user->memberships()
            ->where('status', 'active')
            ->with(['business', 'branch'])
            ->latest()
            ->get();

        return view('dashboard', compact('businesses', 'teamMemberships'));
    })->name('dashboard');

    Route::get('/businesses/{business}/subscription', [BusinessSubscriptionController::class, 'index'])->name('business.subscription');
    Route::post('/businesses/{business}/subscription/{plan}/checkout', [BusinessSubscriptionPaymentController::class, 'checkout'])->whereNumber('plan')->middleware('throttle:5,1')->name('business.subscription.checkout');
    Route::get('/payments/tbc/return/{uuid}', [BusinessSubscriptionPaymentController::class, 'returned'])->middleware('throttle:10,1')->name('payments.tbc.return');
    Route::get('/businesses/{business}/manage', [BusinessManagementController::class, 'index'])->name('business.manage');
    Route::get('/businesses/{business}/inventory', [BusinessInventoryController::class, 'index'])->name('business.inventory.index');
    Route::post('/businesses/{business}/inventory/{item}/adjust', [BusinessInventoryController::class, 'adjust'])->whereNumber('item')->middleware('throttle:30,1')->name('business.inventory.adjust');
    Route::get('/businesses/{business}/orders', [BusinessCartController::class, 'orders'])->name('business.orders.index');
    Route::get('/businesses/{business}/kitchen', [BusinessCartController::class, 'kitchen'])->name('business.kitchen');
    Route::patch('/businesses/{business}/orders/{order}/status', [BusinessCartController::class, 'updateStatus'])->whereNumber('order')->name('business.orders.update-status');
    Route::get('/businesses/{business}/catalog', [BusinessCatalogController::class, 'index'])->name('business.catalog.index');
    Route::post('/businesses/{business}/catalog', [BusinessCatalogController::class, 'store'])->name('business.catalog.store');
    Route::put('/businesses/{business}/catalog/{item}', [BusinessCatalogController::class, 'update'])->whereNumber('item')->name('business.catalog.update');
    Route::delete('/businesses/{business}/catalog/{item}', [BusinessCatalogController::class, 'destroy'])->whereNumber('item')->name('business.catalog.destroy');
    Route::patch('/businesses/{business}/orders/{order}', [BusinessCatalogController::class, 'updateOrderStatus'])->whereNumber('order')->name('business.orders.status');
    Route::get('/businesses/{business}/smart-pages', [SmartPageController::class, 'index'])->name('business.smart-pages.index');
    Route::post('/businesses/{business}/smart-pages', [SmartPageController::class, 'store'])->name('business.smart-pages.store');
    Route::get('/businesses/{business}/smart-pages/{page}/edit', [SmartPageController::class, 'edit'])->name('business.smart-pages.edit');
    Route::put('/businesses/{business}/smart-pages/{page}', [SmartPageController::class, 'update'])->name('business.smart-pages.update');
    Route::delete('/businesses/{business}/smart-pages/{page}', [SmartPageController::class, 'destroy'])->name('business.smart-pages.destroy');
    Route::post('/businesses/{business}/smart-pages/{page}/schedules', [SmartPageController::class, 'storeSchedule'])->name('business.smart-pages.schedules.store');
    Route::delete('/businesses/{business}/smart-page-schedules/{schedule}', [SmartPageController::class, 'destroySchedule'])->whereNumber('schedule')->name('business.smart-pages.schedules.destroy');

    Route::get('/businesses/{business}/profile', [BusinessProfileController::class, 'edit'])->name('business.profile.edit');
    Route::put('/businesses/{business}/profile', [BusinessProfileController::class, 'update'])->name('business.profile.update');
    Route::post('/businesses/{business}/branches', [BusinessManagementController::class, 'storeBranch'])->name('business.branches.store');
    Route::delete('/businesses/{business}/branches/{branch}', [BusinessManagementController::class, 'destroyBranch'])->whereNumber('branch')->name('business.branches.destroy');
    Route::post('/businesses/{business}/members', [BusinessManagementController::class, 'inviteMember'])->name('business.members.invite');
    Route::delete('/businesses/{business}/members/{membership}', [BusinessManagementController::class, 'destroyMember'])->whereNumber('membership')->name('business.members.destroy');
    Route::get('/team/invitations', [BusinessManagementController::class, 'invitations'])->name('team.invitations');
    Route::post('/team/invitations/{membership}/accept', [BusinessManagementController::class, 'acceptInvitation'])->whereNumber('membership')->name('team.invitations.accept');

    Route::get('/businesses/{business}/qr', [BusinessQrController::class, 'index'])->name('business.qr.index');
    Route::get('/businesses/{business}/tables', [BusinessQrController::class, 'tables'])->name('business.tables.index');
    Route::post('/businesses/{business}/qr', [BusinessQrController::class, 'store'])->name('business.qr.store');
    Route::put('/businesses/{business}/qr/{qrCode}', [BusinessQrController::class, 'update'])->whereNumber('qrCode')->name('business.qr.update');
    Route::delete('/businesses/{business}/qr/{qrCode}', [BusinessQrController::class, 'destroy'])->whereNumber('qrCode')->name('business.qr.destroy');
    Route::get('/businesses/{business}/analytics', [QrAnalyticsController::class, 'index'])->name('business.analytics');\n    Route::get('/businesses/{business}/analytics/export', [QrAnalyticsController::class, 'export'])->name('business.analytics.export');
    Route::get('/businesses/{business}/qr/{qrCode}/image.svg', [QrImageController::class, 'show'])->whereNumber('qrCode')->name('business.qr.image');
    Route::get('/businesses/{business}/qr/{qrCode}/print', [QrImageController::class, 'printCard'])->whereNumber('qrCode')->name('business.qr.print');

    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});

    Route::get('/payments/tbc/orders/return/{uuid}', [BusinessOrderPaymentController::class, 'returned'])->middleware('throttle:10,1')->name('business.orders.payment.return');

Route::get('/b/{slug}/cart', [BusinessCartController::class, 'show'])->name('business.cart.show');
Route::post('/b/{slug}/cart/{item}', [BusinessCartController::class, 'add'])->whereNumber('item')->middleware('throttle:30,1')->name('business.cart.add');
Route::patch('/b/{slug}/cart/{item}', [BusinessCartController::class, 'update'])->whereNumber('item')->name('business.cart.update');
Route::delete('/b/{slug}/cart/{item}', [BusinessCartController::class, 'remove'])->whereNumber('item')->name('business.cart.remove');
Route::post('/b/{slug}/checkout', [BusinessCartController::class, 'checkout'])->middleware('throttle:10,1')->name('business.cart.checkout');
Route::post('/b/{slug}/catalog/{item}/order', [BusinessCatalogController::class, 'submitOrder'])->whereNumber('item')->middleware('throttle:10,1')->name('business.catalog.order');
Route::get('/p/{slug}', [BusinessProfileController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('business.public');
Route::get('/b/{slug}', [BusinessProfileController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('business.public.legacy');
Route::get('/q/{publicId}', QrRedirectController::class)->whereUuid('publicId')->name('qr.redirect');
