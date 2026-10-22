# Renderdex

Muestra qué juegos de tu biblioteca de Steam soportan DLSS, FSR, XeSS (y sus variantes de Frame Generation) y Ray Tracing, usando datos de [PCGamingWiki](https://www.pcgamingwiki.com/).

> En desarrollo. La documentación completa (capturas, demo, arquitectura y decisiones técnicas) llegará con la primera versión publicada.

## Correrlo localmente

Requisitos: PHP 8.4+, Composer, Node.js y PostgreSQL 16+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# Crear la base de datos "renderdex" en PostgreSQL y completar DB_* y STEAM_API_KEY en .env
php artisan migrate
composer run dev
```

## Licencias

- Código: [MIT](LICENSE).
- Datos de compatibilidad: [PCGamingWiki](https://www.pcgamingwiki.com/), bajo [CC BY-NC-SA 3.0](https://creativecommons.org/licenses/by-nc-sa/3.0/).

Renderdex no está afiliado a Valve, NVIDIA, AMD ni Intel.
