{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach (['home', 'catalog', 'privacy'] as $route)
        <url>
            <loc>{{ route($route) }}</loc>
        </url>
    @endforeach
    @foreach ($games as $game)
        <url>
            <loc>{{ $game->url() }}</loc>
            <lastmod>{{ $game->synced_at->toDateString() }}</lastmod>
        </url>
    @endforeach
</urlset>
