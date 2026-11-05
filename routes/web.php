<?php

use App\Http\Controllers\BusinessRegistrationController;
use App\Http\Controllers\SessionController;
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
        $businesses = auth()->user()->businesses()->latest()->get();

        return view('dashboard', compact('businesses'));
    })->name('dashboard');

    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
});

Route::get('/q/{publicId}', QrRedirectController::class)->whereUuid('publicId')->name('qr.redirect');
