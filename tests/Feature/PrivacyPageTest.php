<?php

it('links to the privacy policy from every page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('privacy'));
});

it('explains what is queried, what is kept and what the Steam session stores', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee(__('privacy.heading'))
        ->assertSee('Solo consultamos un perfil cuando tú lo pides')
        ->assertSee('durante 1 hora')
        ->assertSee('Junto con tu nombre visible y tu avatar, vive solo en la sesión')
        ->assertSee('No usamos cookies de terceros, analítica ni publicidad.')
        ->assertSee(config('app.repository_url'));
});

it('is served at the Spanish URL', function () {
    expect(route('privacy', absolute: false))->toBe('/privacidad');
});
