<?php

namespace App\Providers;

use App\Services\Steam\DemoSteamClient;
use App\Services\Steam\SteamClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Steam\Provider as SteamProvider;

class AppServiceProvider extends ServiceProvider
{
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
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('steam', SteamProvider::class);
        });
    }
}
