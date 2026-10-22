<?php

use App\Enums\Support;
use App\Services\FeatureClassifier;

// Los valores de entrada vienen de filas reales de PCGamingWiki (octubre de 2026).

it('classifies upscalers by technology regardless of version', function (string $upscaling, Support $dlss, Support $fsr, Support $xess) {
    $result = (new FeatureClassifier)->classify($upscaling, null, null);

    expect($result)
        ->dlss->toBe($dlss)
        ->fsr->toBe($fsr)
        ->xess->toBe($xess);
})->with([
    'Mirror\'s Edge' => ['DLSS 4.5,NIS,TAAU,XeSS 2.1', Support::Yes, Support::No, Support::Yes],
    'Dota 2 (solo FSR 1)' => ['FSR 1', Support::No, Support::Yes, Support::No],
    'EVE Online' => ['FSR 1,FSR 3.1,XeSS 2,DLSS 4,MetalFX', Support::Yes, Support::Yes, Support::Yes],
    'solo upscalers ajenos' => ['TAAU', Support::No, Support::No, Support::No],
    'versión mayor sin decimales' => ['DLSS 2', Support::Yes, Support::No, Support::No],
]);

it('classifies frame generation only from the frame gen list', function (string $frameGen, Support $dlssFg, Support $dlssMfg, Support $fsrFg, Support $xessFg) {
    $result = (new FeatureClassifier)->classify('DLSS 4.5,FSR 3.1,XeSS 2', $frameGen, null);

    expect($result)
        ->dlss_fg->toBe($dlssFg)
        ->dlss_mfg->toBe($dlssMfg)
        ->fsr_fg->toBe($fsrFg)
        ->xess_fg->toBe($xessFg);
})->with([
    'War Thunder (todas)' => [
        'DLSS Frame Generation,DLSS Multi-Frame Generation,FSR Frame Generation,XeSS Frame Generation',
        Support::Yes, Support::Yes, Support::Yes, Support::Yes,
    ],
    'Portal (solo DLSS FG)' => ['DLSS Frame Generation', Support::Yes, Support::No, Support::No, Support::No],
    'MFG sin listar FG' => ['DLSS Multi-Frame Generation', Support::Yes, Support::Yes, Support::No, Support::No],
    'solo frame gen ajeno' => ['MetalFX Frame Interpolation', Support::No, Support::No, Support::No, Support::No],
]);

it('leaves technologies unknown when the wiki lists nothing for them', function (?string $upscaling, ?string $frameGen) {
    $result = (new FeatureClassifier)->classify($upscaling, $frameGen, null);

    expect($result)->each->toBe(Support::Unknown);
})->with([
    'sin datos' => [null, null],
    'listas vacías' => ['', ''],
    'solo separadores y espacios' => [' , ', ','],
]);

it('keeps frame generation unknown when only upscaling is documented', function () {
    $result = (new FeatureClassifier)->classify('DLSS 3.7', null, null);

    expect($result)
        ->dlss->toBe(Support::Yes)
        ->dlss_fg->toBe(Support::Unknown)
        ->dlss_mfg->toBe(Support::Unknown);
});

it('never infers FSR frame generation from FSR upscaling versions', function (string $upscaling) {
    $result = (new FeatureClassifier)->classify($upscaling, null, null);

    expect($result)
        ->fsr->toBe(Support::Yes)
        ->fsr_fg->toBe(Support::Unknown);
})->with(['FSR 1', 'FSR 3', 'FSR 3.1', 'FSR 4.1']);

it('does not treat values that only start with a technology name as that technology', function () {
    $result = (new FeatureClassifier)->classify('DLSS Frame Generation', null, null);

    expect($result)->dlss->toBe(Support::No);
});

it('ignores case and surrounding whitespace in list values', function () {
    $result = (new FeatureClassifier)->classify(' dlss 4 , xess 1.3 ', ' dlss frame generation ', null);

    expect($result)
        ->dlss->toBe(Support::Yes)
        ->xess->toBe(Support::Yes)
        ->dlss_fg->toBe(Support::Yes);
});

it('classifies ray tracing from its state', function (?string $state, Support $expected) {
    $result = (new FeatureClassifier)->classify(null, null, $state);

    expect($result['ray_tracing'])->toBe($expected);
})->with([
    'true' => ['true', Support::Yes],
    'limited' => ['limited', Support::Yes],
    'hackable' => ['hackable', Support::Yes],
    'always on' => ['always on', Support::Yes],
    'false' => ['false', Support::No],
    'n/a' => ['n/a', Support::No],
    'unknown' => ['unknown', Support::Unknown],
    'vacío' => ['', Support::Unknown],
    'null' => [null, Support::Unknown],
    'mayúsculas' => ['TRUE', Support::Yes],
]);
