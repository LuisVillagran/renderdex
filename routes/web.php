<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SteamAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/auth/steam', [SteamAuthController::class, 'redirect'])->name('auth.steam');
Route::get('/auth/steam/callback', [SteamAuthController::class, 'callback'])->name('auth.steam.callback');
Route::post('/logout', [SteamAuthController::class, 'logout'])->name('logout');

Route::view('/u/{steamId}', 'library.show')
    ->where('steamId', '7656119\d{10}')
    ->name('library.show');
