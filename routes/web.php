<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SteamAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/juegos', 'catalog')->name('catalog');
Route::get('/juegos/{game}', [GameController::class, 'show'])
    ->where('game', '\d+(?:-[a-z0-9-]+)?')
    ->name('games.show');
Route::view('/privacidad', 'privacy')->name('privacy');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
// Dinámico para poder dar la URL absoluta del sitemap, como pide el estándar.
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow:\n\nSitemap: ".route('sitemap')."\n")
    ->header('Content-Type', 'text/plain'))->name('robots');

Route::get('/auth/steam', [SteamAuthController::class, 'redirect'])->name('auth.steam');
Route::get('/auth/steam/callback', [SteamAuthController::class, 'callback'])
    ->middleware('throttle:steam')
    ->name('auth.steam.callback');
Route::post('/logout', [SteamAuthController::class, 'logout'])->name('logout');

Route::view('/u/{steamId}', 'library.show')
    ->where('steamId', '7656119\d{10}')
    ->middleware('throttle:steam')
    ->name('library.show');
