<?php

use App\Http\Controllers\SteamAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/auth/steam', [SteamAuthController::class, 'redirect'])->name('auth.steam');
Route::get('/auth/steam/callback', [SteamAuthController::class, 'callback'])->name('auth.steam.callback');
Route::post('/logout', [SteamAuthController::class, 'logout'])->name('logout');

// Provisional hasta la etapa 4 (UI).
Route::view('/u/{steamId}', 'library.show')
    ->where('steamId', '7656119\d{10}')
    ->name('library.show');
