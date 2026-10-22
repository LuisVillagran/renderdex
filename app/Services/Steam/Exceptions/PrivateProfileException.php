<?php

namespace App\Services\Steam\Exceptions;

/**
 * GetOwnedGames no devolvió juegos: el perfil es privado o tiene los "Detalles del juego" privados.
 * Steam responde igual en ambos casos, así que no se pueden distinguir.
 */
class PrivateProfileException extends SteamException {}
