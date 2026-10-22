{{-- Provisional hasta la etapa 4 (UI). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body>
    <p>{{ __('library.coming_soon') }}</p>

    @if (session('steam_user'))
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">{{ __('auth.logout') }}</button>
        </form>
    @endif
</body>
</html>
