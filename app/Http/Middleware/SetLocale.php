<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idioma de la interfaz: el elegido con ?lang= (se recuerda en la sesión), el de la sesión,
 * el preferido por el navegador o, si no hay ninguno soportado, el de la configuración.
 * Las URLs no cambian con el idioma.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales');
        $chosen = $request->query('lang');

        if (is_string($chosen) && in_array($chosen, $supported, true)) {
            $request->session()->put('locale', $chosen);
        }

        $sessionLocale = $request->session()->get('locale');

        $locale = match (true) {
            in_array($sessionLocale, $supported, true) => $sessionLocale,
            $request->headers->has('Accept-Language') => $request->getPreferredLanguage($supported) ?? config('app.locale'),
            default => config('app.locale'),
        };

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        // Number::format() usa inglés por defecto ("90.2"); en español debe ser "90,2".
        Number::useLocale($locale);

        return $next($request);
    }
}
