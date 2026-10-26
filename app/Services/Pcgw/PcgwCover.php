<?php

namespace App\Services\Pcgw;

/**
 * URLs de las portadas de PCGamingWiki. Clase pura.
 *
 * La wiki puede guardar la portada como URL completa o como nombre de archivo ("File:Hades cover.jpg");
 * se guarda siempre como URL, y para mostrarla se pide una miniatura en vez del original, que puede
 * pesar varios cientos de KB.
 */
class PcgwCover
{
    private const IMAGES_HOST = 'images.pcgamingwiki.com';

    /** Redirige al archivo y acepta ?width= para pedir una miniatura. */
    private const FILE_PATH_URL = 'https://www.pcgamingwiki.com/wiki/Special:FilePath/';

    /**
     * Convierte el valor del campo de la wiki en una URL https, o null si no sirve.
     */
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            $url = preg_replace('#^http://#i', 'https://', $value);

            // Solo imágenes de la propia wiki: no se cargan URLs arbitrarias en la página.
            return in_array(parse_url($url, PHP_URL_HOST), [self::IMAGES_HOST, 'www.pcgamingwiki.com', 'pcgamingwiki.com'], true) ? $url : null;
        }

        $file = preg_replace('/^(File|Image|Archivo):/i', '', $value);

        return $file === '' ? null : self::FILE_PATH_URL.rawurlencode(str_replace(' ', '_', $file));
    }

    /**
     * Miniatura de MediaWiki de ese ancho, o la misma URL si no se reconoce el formato.
     */
    public static function thumbnail(string $url, int $width): string
    {
        // https://images.pcgamingwiki.com/a/ab/Archivo.jpg → /thumb/a/ab/Archivo.jpg/120px-Archivo.jpg
        if (preg_match('#^https://'.preg_quote(self::IMAGES_HOST, '#').'/([0-9a-f])/([0-9a-f]{2})/([^/?\#]+)$#', $url, $parts) === 1) {
            [, $first, $second, $file] = $parts;

            return 'https://'.self::IMAGES_HOST."/thumb/{$first}/{$second}/{$file}/{$width}px-{$file}";
        }

        if (str_starts_with($url, self::FILE_PATH_URL)) {
            return $url.'?width='.$width;
        }

        return $url;
    }
}
