<?php

namespace App\Services;

use App\Enums\Support;
use App\Enums\Technology;

/**
 * Clasifica el soporte de cada tecnología a partir de los campos `Video` de PCGamingWiki.
 *
 * La wiki no guarda un estado aparte para upscaling ni frame gen: una lista vacía
 * significa "no documentado", no "no soportado".
 */
class FeatureClassifier
{
    private const RAY_TRACING_YES = ['true', 'limited', 'hackable', 'always on'];

    private const RAY_TRACING_NO = ['false', 'n/a'];

    /**
     * @param  string|null  $upscaling  Lista separada por comas, p. ej. "DLSS 4.5,FSR 3.1".
     * @param  string|null  $frameGen  Lista separada por comas, p. ej. "DLSS Frame Generation".
     * @param  string|null  $rayTracing  Estado, p. ej. "true" o "n/a".
     * @return array<string, Support> Indexado por el valor de {@see Technology}.
     */
    public function classify(?string $upscaling, ?string $frameGen, ?string $rayTracing): array
    {
        $upscalers = $this->parseList($upscaling);
        $frameGenerators = $this->parseList($frameGen);

        return [
            Technology::Dlss->value => $this->fromList($upscalers, fn (string $value) => $this->isVersioned($value, 'dlss')),
            Technology::DlssFg->value => $this->fromList($frameGenerators, fn (string $value) => in_array($value, ['dlss frame generation', 'dlss multi-frame generation'], true)),
            Technology::DlssMfg->value => $this->fromList($frameGenerators, fn (string $value) => $value === 'dlss multi-frame generation'),
            Technology::Fsr->value => $this->fromList($upscalers, fn (string $value) => $this->isVersioned($value, 'fsr')),
            Technology::FsrFg->value => $this->fromList($frameGenerators, fn (string $value) => $value === 'fsr frame generation'),
            Technology::Xess->value => $this->fromList($upscalers, fn (string $value) => $this->isVersioned($value, 'xess')),
            Technology::XessFg->value => $this->fromList($frameGenerators, fn (string $value) => $value === 'xess frame generation'),
            Technology::RayTracing->value => $this->fromRayTracing($rayTracing),
        ];
    }

    /**
     * La versión más alta de un reescalador en la lista, p. ej. "4" para FSR en "FSR 2.1,FSR 4".
     *
     * @param  string|null  $below  Solo versiones menores que esta, p. ej. "4" para quedarse con FSR 3.x.
     * @return string|null Null si la tecnología no aparece con versión o no es un reescalador.
     */
    public function latestVersion(?string $upscaling, Technology $technology, ?string $below = null): ?string
    {
        if (! in_array($technology, [Technology::Dlss, Technology::Fsr, Technology::Xess], true)) {
            return null;
        }

        return collect($this->parseList($upscaling))
            ->map(fn (string $value) => preg_match('/^'.$technology->value.'\s+(\d+(?:\.\d+)*)$/', $value, $matches) === 1 ? $matches[1] : null)
            ->filter()
            ->when($below !== null, fn ($versions) => $versions->filter(fn (string $version) => version_compare($version, $below, '<')))
            ->sort(fn (string $a, string $b) => version_compare($a, $b))
            ->last();
    }

    /**
     * @return list<string>
     */
    private function parseList(?string $list): array
    {
        return collect(explode(',', (string) $list))
            ->map(fn (string $value) => mb_strtolower(trim($value)))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $values
     * @param  callable(string): bool  $matches
     */
    private function fromList(array $values, callable $matches): Support
    {
        if ($values === []) {
            return Support::Unknown;
        }

        return collect($values)->contains($matches) ? Support::Yes : Support::No;
    }

    /**
     * Acepta "fsr" o "fsr <versión>", pero no otros valores que empiecen igual.
     */
    private function isVersioned(string $value, string $technology): bool
    {
        return preg_match('/^'.$technology.'(\s+\d+(\.\d+)*)?$/', $value) === 1;
    }

    private function fromRayTracing(?string $state): Support
    {
        $state = mb_strtolower(trim((string) $state));

        return match (true) {
            in_array($state, self::RAY_TRACING_YES, true) => Support::Yes,
            in_array($state, self::RAY_TRACING_NO, true) => Support::No,
            default => Support::Unknown,
        };
    }
}
