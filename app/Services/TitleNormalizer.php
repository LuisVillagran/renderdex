<?php

namespace App\Services;

/**
 * Normaliza títulos de juegos para compararlos entre Steam y PCGamingWiki.
 *
 * Es deliberadamente conservador: solo elimina diferencias tipográficas, nunca palabras,
 * para que "Cyberpunk 2077" y "Cyberpunk 2077: Ultimate Edition" sigan siendo distintos.
 */
class TitleNormalizer
{
    private const REPLACEMENTS = [
        '™' => '',
        '®' => '',
        '©' => '',
        '’' => "'",
        '‘' => "'",
        '`' => "'",
        '“' => '"',
        '”' => '"',
        '–' => '-',
        '—' => '-',
    ];

    public function normalize(string $title): string
    {
        $title = strtr($title, self::REPLACEMENTS);
        $title = (string) preg_replace('/\s+/u', ' ', $title);

        return mb_strtolower(trim($title));
    }
}
