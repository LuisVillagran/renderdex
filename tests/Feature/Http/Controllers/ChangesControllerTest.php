<?php

use App\Enums\ChangeType;
use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;
use App\Models\GameChange;

it('lists what the wiki documented, grouped by day and game', function () {
    $cyberpunk = Game::factory()->create(['title' => 'Cyberpunk 2077']);
    GameChange::factory()->for($cyberpunk)->create(['technology' => Technology::Dlss, 'type' => ChangeType::Upgraded, 'previous_version' => '3.5', 'version' => '4.5']);
    GameChange::factory()->for($cyberpunk)->create(['technology' => Technology::Xess, 'type' => ChangeType::Removed, 'version' => null]);

    $this->get(route('changes.index'))
        ->assertOk()
        ->assertSee(__('changes.heading'))
        ->assertSee($cyberpunk->url())
        ->assertSee('DLSS 3.5 → 4.5')
        ->assertSee(__('changes.type_label.removed'))
        ->assertSee(route('changes.feed'));
});

it('filters by capability', function () {
    GameChange::factory()->for(Game::factory()->create(['title' => 'Has RT']))->create(['technology' => Technology::RayTracing, 'version' => null]);
    GameChange::factory()->for(Game::factory()->create(['title' => 'Has DLSS']))->create(['technology' => Technology::Dlss]);

    $this->get(route('changes.index', ['cap' => 'ray_tracing']))
        ->assertSee('Has RT')
        ->assertDontSee('Has DLSS');
});

it('says when there are no changes yet', function () {
    $this->get(route('changes.index'))->assertSee(__('changes.empty'));
});

it('publishes an Atom feed', function () {
    $game = Game::factory()->create(['title' => 'Hades']);
    GameChange::factory()->for($game)->create(['technology' => Technology::Fsr, 'version' => '3.1']);

    $this->get(route('changes.feed'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8')
        ->assertSee('<feed xmlns="http://www.w3.org/2005/Atom"', escape: false)
        ->assertSee('Hades: Agregado FSR 3.1')
        ->assertSee($game->url());
});

it('shows the latest changes on the game page', function () {
    $game = Game::factory()->create(['pcgw_page_id' => 5, 'title' => 'Hades', 'fsr' => Support::Yes, 'upscaling_tech' => 'FSR 3.1']);
    GameChange::factory()->for($game)->create(['technology' => Technology::Fsr, 'version' => '3.1']);

    $this->get($game->url())->assertSee(__('game.changes'))->assertSee('FSR 3.1');
});
