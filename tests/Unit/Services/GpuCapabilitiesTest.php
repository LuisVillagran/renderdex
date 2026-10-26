<?php

use App\Enums\Availability;
use App\Enums\GpuProfile;
use App\Enums\Support;
use App\Enums\Technology;
use App\Enums\Vendor;
use App\Models\Game;
use App\Services\FeatureClassifier;
use App\Services\GpuCapabilities;

function gpus(): GpuCapabilities
{
    return new GpuCapabilities(new FeatureClassifier);
}

/**
 * Juego en memoria (sin base de datos) que soporta todas las tecnologías.
 */
function fullySupportedGame(?string $upscaling = 'DLSS 4,FSR 3.1,FSR 4,XeSS 2'): Game
{
    $game = new Game(collect(Technology::cases())->mapWithKeys(fn (Technology $technology) => [$technology->value => Support::Yes])->all());
    $game->upscaling_tech = $upscaling;

    return $game;
}

it('knows what each GPU generation can run', function (GpuProfile $gpu, array $usable, array $depends) {
    foreach (Technology::cases() as $technology) {
        $expected = match (true) {
            in_array($technology, $usable, true) => Availability::Usable,
            in_array($technology, $depends, true) => Availability::Depends,
            default => Availability::NeedsOtherGpu,
        };

        expect(gpus()->hardware($gpu, $technology))->toBe($expected, "{$gpu->value} / {$technology->value}");
    }
})->with([
    'RTX 50: todo' => [GpuProfile::Rtx50, [Technology::Dlss, Technology::DlssFg, Technology::DlssMfg, Technology::Fsr, Technology::FsrFg, Technology::Xess, Technology::RayTracing], [Technology::XessFg]],
    'RTX 40: sin MFG' => [GpuProfile::Rtx40, [Technology::Dlss, Technology::DlssFg, Technology::Fsr, Technology::FsrFg, Technology::Xess, Technology::RayTracing], [Technology::XessFg]],
    'RTX 20/30: DLSS sin FG, pero FSR FG sí' => [GpuProfile::Rtx20To30, [Technology::Dlss, Technology::Fsr, Technology::FsrFg, Technology::Xess, Technology::RayTracing], [Technology::XessFg]],
    'GTX: solo reescalado de AMD e Intel' => [GpuProfile::Gtx, [Technology::Fsr, Technology::Xess], [Technology::XessFg]],
    'RX 9000' => [GpuProfile::Rx9000, [Technology::Fsr, Technology::FsrFg, Technology::Xess, Technology::RayTracing], [Technology::XessFg]],
    'RX 6000' => [GpuProfile::Rx6000, [Technology::Fsr, Technology::FsrFg, Technology::Xess, Technology::RayTracing], [Technology::XessFg]],
    'RX 5000: sin RT' => [GpuProfile::Rx5000, [Technology::Fsr, Technology::FsrFg, Technology::Xess], [Technology::XessFg]],
    'Arc: XeSS FG nativo' => [GpuProfile::Arc, [Technology::Fsr, Technology::FsrFg, Technology::Xess, Technology::XessFg, Technology::RayTracing], []],
]);

it('only says usable when the game supports it', function () {
    $game = new Game(['dlss' => Support::No, 'fsr' => Support::Unknown, 'xess' => Support::Yes, 'dlss_fg' => Support::Yes]);

    expect(gpus()->availability($game, GpuProfile::Rtx40, Technology::Dlss))->toBe(Availability::NotSupported)
        ->and(gpus()->availability($game, GpuProfile::Rtx40, Technology::Fsr))->toBe(Availability::Unknown)
        ->and(gpus()->availability($game, GpuProfile::Rtx40, Technology::Xess))->toBe(Availability::Usable)
        ->and(gpus()->availability($game, GpuProfile::Rtx20To30, Technology::DlssFg))->toBe(Availability::NeedsOtherGpu)
        ->and(gpus()->availability(null, GpuProfile::Rtx40, Technology::Dlss))->toBe(Availability::Unknown);
});

it('uses the FSR 3.1 fallback on GPUs that cannot run FSR 4', function () {
    $game = fullySupportedGame();

    expect(gpus()->usableVersion($game, GpuProfile::Rx9000, Technology::Fsr))->toBe('4')
        ->and(gpus()->usableVersion($game, GpuProfile::Rx7000, Technology::Fsr))->toBe('4')
        ->and(gpus()->usableVersion($game, GpuProfile::Rx6000, Technology::Fsr))->toBe('3.1')
        ->and(gpus()->usableVersion($game, GpuProfile::Rtx40, Technology::Fsr))->toBe('3.1')
        ->and(gpus()->usableVersion(fullySupportedGame('FSR 4'), GpuProfile::Rtx40, Technology::Fsr))->toBeNull()
        ->and(gpus()->usableVersion($game, GpuProfile::Rtx40, Technology::Dlss))->toBe('4')
        ->and(gpus()->usableVersion($game, GpuProfile::Rx9000, Technology::Dlss))->toBeNull();
});

it('groups the generations by vendor, newest first', function () {
    expect(GpuProfile::forVendor(Vendor::Amd))->toBe([GpuProfile::Rx9000, GpuProfile::Rx7000, GpuProfile::Rx6000, GpuProfile::Rx5000]);
});
