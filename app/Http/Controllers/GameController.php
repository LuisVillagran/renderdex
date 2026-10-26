<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\SupportSummary;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class GameController extends Controller
{
    /** Últimos cambios de la wiki que se muestran en la ficha. */
    public const CHANGES_SHOWN = 10;

    /**
     * Ficha de un juego. La URL lleva el ID de la wiki y un slug que solo sirve para leerla:
     * si el slug no coincide (p. ej. la wiki renombró la página), se redirige a la canónica.
     */
    public function show(string $game, SupportSummary $summary): View|RedirectResponse
    {
        $model = Game::with('steamAppids')->where('pcgw_page_id', (int) $game)->firstOrFail();

        if ($game !== $model->routeKey()) {
            return redirect()->to($model->url(), 301);
        }

        return view('games.show', [
            'game' => $model,
            'appid' => $model->primarySteamAppid(),
            'supported' => $summary->labels($model),
            'changes' => $model->changes()->orderByDesc('detected_at')->orderBy('id')->limit(self::CHANGES_SHOWN)->get(),
        ]);
    }
}
