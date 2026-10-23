<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameSteamAppid;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Juegos de la vista previa, por Steam AppID: uno con todo, uno mixto y uno con poco soporte.
     * Se leen de la base, así que muestran los datos reales del último sync.
     */
    public const SHOWCASE_APPIDS = [1091500, 1716740, 2050650];

    public function __invoke(): View
    {
        $showcase = GameSteamAppid::query()
            ->with('game')
            ->whereIn('steam_appid', self::SHOWCASE_APPIDS)
            ->get()
            ->sortBy(fn (GameSteamAppid $appid) => array_search($appid->steam_appid, self::SHOWCASE_APPIDS, true))
            ->values();

        return view('home', [
            'showcase' => $showcase,
            'gameCount' => Game::count(),
        ]);
    }
}
