<?php

use App\Http\Controllers\BusinessManagementController;
use App\Http\Controllers\BusinessProfileController;
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
    Route::get('/businesses/{business}/catalog', [BusinessCatalogController::class, 'index'])->name('business.catalog.index');
    Route::post('/businesses/{business}/catalog', [BusinessCatalogController::class, 'store'])->name('business.catalog.store');
    Route::put('/businesses/{business}/catalog/{item}', [BusinessCatalogController::class, 'update'])->whereNumber('item')->name('business.catalog.update');
    Route::delete('/businesses/{business}/catalog/{item}', [BusinessCatalogController::class, 'destroy'])->whereNumber('item')->name('business.catalog.destroy');
    Route::patch('/businesses/{business}/orders/{order}', [BusinessCatalogController::class, 'updateOrderStatus'])->whereNumber('order')->name('business.orders.status');
    Route::get('/businesses/{business}/profile', [BusinessProfileController::class, 'edit'])->name('business.profile.edit');
    Route::put('/businesses/{business}/profile', [BusinessProfileController::class, 'update'])->name('business.profile.update');
    Route::post('/businesses/{business}/branches', [BusinessManagementController::class, 'storeBranch'])->name('business.branches.store');
    Route::delete('/businesses/{business}/branches/{branch}', [BusinessManagementController::class, 'destroyBranch'])->whereNumber('branch')->name('business.branches.destroy');
    Route::post('/businesses/{business}/members', [BusinessManagementController::class, 'inviteMember'])->name('business.members.invite');
    Route::delete('/businesses/{business}/members/{membership}', [BusinessManagementController::class, 'destroyMember'])->whereNumber('membership')->name('business.members.destroy');
    Route::get('/team/invitations', [BusinessManagementController::class, 'invitations'])->name('team.invitations');
    Route::post('/team/invitations/{membership}/accept', [BusinessManagementController::class, 'acceptInvitation'])->whereNumber('membership')->name('team.invitations.accept');

    Route::get('/businesses/{business}/qr', [BusinessQrController::class, 'index'])->name('business.qr.index');
    Route::post('/businesses/{business}/qr', [BusinessQrController::class, 'store'])->name('business.qr.store');
    Route::put('/businesses/{business}/qr/{qrCode}', [BusinessQrController::class, 'update'])->whereNumber('qrCode')->name('business.qr.update');
    Route::delete('/businesses/{business}/qr/{qrCode}', [BusinessQrController::class, 'destroy'])->whereNumber('qrCode')->name('business.qr.destroy');
    Route::get('/businesses/{business}/analytics', [QrAnalyticsController::class, 'index'])->name('business.analytics');
    Route::get('/businesses/{business}/qr/{qrCode}/image.svg', [QrImageController::class, 'show'])->whereNumber('qrCode')->name('business.qr.image');

    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});

Route::post('/b/{slug}/catalog/{item}/order', [BusinessCatalogController::class, 'submitOrder'])->whereNumber('item')->middleware('throttle:10,1')->name('business.catalog.order');
Route::get('/b/{slug}', [BusinessProfileController::class, 'show'])->where('slug', '[A-Za-z0-9-]+')->name('business.public');
Route::get('/q/{publicId}', QrRedirectController::class)->whereUuid('publicId')->name('qr.redirect');
