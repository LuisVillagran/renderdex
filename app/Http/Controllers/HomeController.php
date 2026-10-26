<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameSteamAppid;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Mosaico de portadas del inicio, por Steam AppID: juegos conocidos y con datos en la wiki.
     * Se leen de la base, así que muestran los datos reales del último sync.
     */
    public const SHOWCASE_APPIDS = [1091500, 1245620, 2358720, 1086940, 292030, 2050650, 1174180, 1145360, 2215430, 990080, 1716740, 553850];

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
