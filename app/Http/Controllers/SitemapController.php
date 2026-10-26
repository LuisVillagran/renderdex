<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Solo los juegos con algún dato: las ~50.000 fichas vacías no le sirven a nadie que busque.
     */
    public function __invoke(): Response
    {
        $games = Game::query()
            ->where('has_data', true)
            ->orderBy('pcgw_page_id')
            ->get(['pcgw_page_id', 'title', 'synced_at']);

        return response()
            ->view('sitemap', ['games' => $games])
            ->header('Content-Type', 'application/xml');
    }
}
