<?php

namespace App\Enums;

/**
 * Cómo se encontró la página de la wiki de un juego de la biblioteca.
 */
enum MatchSource: string
{
    case AppId = 'appid';

    /** Respaldo: título exacto normalizado, para juegos sin Steam AppID en la wiki. */
    case Title = 'title';
}
