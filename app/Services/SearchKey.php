<?php

namespace App\Services;

/**
 * Clave de búsqueda de un título: más permisiva que {@see TitleNormalizer}, que se usa
 * para cruzar bibliotecas y debe seguir siendo conservador.
 *
 * "The Witcher 3: Wild Hunt" → "the witcher 3 wild hunt"; "DOOM II" → "doom 2".
 */
class SearchKey
{
    /** Se quitan sin dejar espacio, para que "Assassin's" y "assassins" coincidan. */
    private const REMOVED = ["'", '"'];

    /**
     * Se reemplazan por un espacio. `%`, `_`, `#` y `+` se conservan porque distinguen
     * títulos ("100% Orange Juice", "C++").
     */
    private const SEPARATORS = [':', ';', ',', '.', '!', '?', '¡', '¿', '(', ')', '[', ']', '{', '}', '/', '\\', '|', '~', '-', '*'];

    private const ROMAN_NUMERALS = [
        'i' => '1', 'ii' => '2', 'iii' => '3', 'iv' => '4', 'v' => '5',
        'vi' => '6', 'vii' => '7', 'viii' => '8', 'ix' => '9', 'x' => '10',
        'xi' => '11', 'xii' => '12', 'xiii' => '13', 'xiv' => '14', 'xv' => '15',
        'xvi' => '16', 'xvii' => '17', 'xviii' => '18', 'xix' => '19', 'xx' => '20',
    ];

    public function __construct(private TitleNormalizer $normalizer) {}

    public function make(string $title): string
    {
        $key = $this->normalizer->normalize($title);
        $key = str_replace(self::REMOVED, '', $key);
        $key = str_replace(self::SEPARATORS, ' ', $key);
        $key = str_replace('&', ' and ', $key);

        return implode(' ', array_map(
            fn (string $word) => self::ROMAN_NUMERALS[$word] ?? $word,
            $this->words($key),
        ));
    }

    /**
     * Palabras de la clave, p. ej. para exigir que todas aparezcan en cualquier orden.
     *
     * @return list<string>
     */
    public function words(string $key): array
    {
        return preg_split('/\s+/u', $key, flags: PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
