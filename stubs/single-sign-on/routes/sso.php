<?php

use App\Http\Controllers\SSO\SSOController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('sso/login', [SSOController::class, 'login'])->name('sso.login');
    Route::get('sso/callback', [SSOController::class, 'callback'])->name('sso.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('sso/logout', [SSOController::class, 'logout'])->name('sso.logout');
});
