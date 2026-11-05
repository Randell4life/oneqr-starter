<?php

use App\Http\Controllers\BusinessRegistrationController;
use App\Http\Controllers\BusinessQrController;
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

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function () {
        $businesses = auth()->user()->businesses()->withCount('qrCodes')->latest()->get();

        return view('dashboard', compact('businesses'));
    })->name('dashboard');

    Route::get('/businesses/{business}/qr', [BusinessQrController::class, 'index'])->name('business.qr.index');
    Route::post('/businesses/{business}/qr', [BusinessQrController::class, 'store'])->name('business.qr.store');
    Route::put('/businesses/{business}/qr/{qrCode}', [BusinessQrController::class, 'update'])->whereNumber('qrCode')->name('business.qr.update');
    Route::delete('/businesses/{business}/qr/{qrCode}', [BusinessQrController::class, 'destroy'])->whereNumber('qrCode')->name('business.qr.destroy');
    Route::get('/businesses/{business}/analytics', [QrAnalyticsController::class, 'index'])->name('business.analytics');
    Route::get('/businesses/{business}/qr/{qrCode}/image.svg', [QrImageController::class, 'show'])->whereNumber('qrCode')->name('business.qr.image');

    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});

Route::get('/q/{publicId}', QrRedirectController::class)->whereUuid('publicId')->name('qr.redirect');
