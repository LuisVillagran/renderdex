<?php

use App\Enums\Support;
use App\Models\Game;

it('lists the static pages and only the games with data', function () {
    $documented = Game::factory()->create(['pcgw_page_id' => 1, 'title' => 'Documented', 'ray_tracing' => Support::No]);
    Game::factory()->create(['pcgw_page_id' => 2, 'title' => 'Empty']);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', escape: false)
        ->assertSee('<loc>'.route('catalog').'</loc>', escape: false)
        ->assertSee('<loc>'.$documented->url().'</loc>', escape: false)
        ->assertSee('<lastmod>'.$documented->synced_at->toDateString().'</lastmod>', escape: false)
        ->assertDontSee('2-empty');
});

it('points crawlers to the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: '.route('sitemap'));
});

it('keeps libraries out of search engines', function () {
    config(['services.steam.key' => null]);

    $this->get(route('library.show', '76561197960287930'))
        ->assertSee('<meta name="robots" content="noindex">', escape: false);
});
