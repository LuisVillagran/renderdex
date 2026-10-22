<?php

use App\Enums\Technology;
use App\Enums\Vendor;

test('each technology belongs to its vendor', function (Technology $technology, Vendor $vendor) {
    expect($technology->vendor())->toBe($vendor);
})->with([
    'DLSS' => [Technology::Dlss, Vendor::Nvidia],
    'DLSS FG' => [Technology::DlssFg, Vendor::Nvidia],
    'DLSS MFG' => [Technology::DlssMfg, Vendor::Nvidia],
    'FSR' => [Technology::Fsr, Vendor::Amd],
    'FSR FG' => [Technology::FsrFg, Vendor::Amd],
    'XeSS' => [Technology::Xess, Vendor::Intel],
    'XeSS FG' => [Technology::XessFg, Vendor::Intel],
    'Ray Tracing' => [Technology::RayTracing, Vendor::All],
]);
