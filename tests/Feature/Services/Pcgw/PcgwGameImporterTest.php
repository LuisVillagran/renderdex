<?php

use App\Enums\ChangeType;
use App\Enums\Technology;
use App\Models\GameChange;
use App\Services\Pcgw\PcgwGame;
use App\Services\Pcgw\PcgwGameImporter;

function pcgwGame(int $pageId, ?string $upscaling = null, ?string $frameGen = null, ?string $rayTracing = null): PcgwGame
{
    return new PcgwGame(pageId: $pageId, title: "Game {$pageId}", steamAppids: [], upscaling: $upscaling, frameGen: $frameGen, rayTracing: $rayTracing);
}

it('records nothing for new pages, so the first sync does not flood the changes', function () {
    app(PcgwGameImporter::class)->import([pcgwGame(1, 'DLSS 4'), pcgwGame(2, 'FSR 3.1')], now());

    expect(GameChange::count())->toBe(0);
});

it('records technologies the wiki adds, removes or upgrades', function () {
    $importer = app(PcgwGameImporter::class);
    $importer->import([pcgwGame(1, 'DLSS 3.5,XeSS 1.3', rayTracing: 'false')], now()->subDay());

    $importer->import([pcgwGame(1, 'DLSS 4.5,FSR 3.1', 'DLSS Frame Generation', 'true')], $syncedAt = now()->startOfSecond());

    $changes = GameChange::orderBy('id')->get()->map(fn (GameChange $change) => [
        $change->technology, $change->type, $change->previous_version, $change->version,
    ])->all();

    expect($changes)->toBe([
        [Technology::Dlss, ChangeType::Upgraded, '3.5', '4.5'],
        [Technology::DlssFg, ChangeType::Added, null, null],
        [Technology::Fsr, ChangeType::Added, null, '3.1'],
        [Technology::Xess, ChangeType::Removed, '1.3', null],
        [Technology::RayTracing, ChangeType::Added, null, null],
    ])->and(GameChange::first()->detected_at->equalTo($syncedAt))->toBeTrue();
});

it('records nothing when the wiki did not change', function () {
    $importer = app(PcgwGameImporter::class);
    $importer->import([pcgwGame(1, 'DLSS 4')], now()->subDay());
    $importer->import([pcgwGame(1, 'DLSS 4')], now());

    expect(GameChange::count())->toBe(0);
});
