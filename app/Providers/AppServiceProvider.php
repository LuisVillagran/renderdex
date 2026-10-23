<?php

namespace App\Providers;

use App\Models\SyncRun;
use App\Services\Steam\DemoSteamClient;
use App\Services\Steam\SteamClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Steam\Provider as SteamProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Páginas que piden datos a Steam (biblioteca y login) por IP y minuto. */
    public const STEAM_REQUESTS_PER_MINUTE = 30;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (DemoSteamClient::shouldReplaceSteam()) {
            $this->app->bind(SteamClient::class, DemoSteamClient::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Number::format() usa inglés por defecto ("90.2"); en español debe ser "90,2".
        Number::useLocale($this->app->getLocale());

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('steam', SteamProvider::class);
        });

        View::composer('components.layout', function ($view) {
            $view->with('lastSyncAt', SyncRun::lastSuccessfulAt());
        });

        // La Steam Web API permite 100.000 llamadas diarias en total: cada IP tiene un cupo.
        RateLimiter::for('steam', function (Request $request) {
            return Limit::perMinute(self::STEAM_REQUESTS_PER_MINUTE)->by($request->ip());
        });
    }
}
