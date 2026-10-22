# Renderdex

Aplicación web de portafolio. El usuario **inicia sesión con Steam** o pega su **URL de perfil o su SteamID**. La app le muestra qué juegos de su biblioteca soportan cada tecnología de render, sin que tenga que instalarlos:

- **NVIDIA:** DLSS, DLSS Frame Generation y DLSS Multi-Frame Generation.
- **AMD:** FSR y FSR Frame Generation.
- **Intel:** XeSS y XeSS Frame Generation.
- **Todas:** Ray Tracing.

Los datos de compatibilidad vienen de **PCGamingWiki**. Se sincronizan una vez al día hacia nuestra base de datos, y la biblioteca del usuario se obtiene de la **Steam Web API**.

**Marca:** el nombre es "Renderdex". No usar "Steam", "NVIDIA", "AMD", "Intel", "RTX", "DLSS", "FSR" ni "XeSS" en el nombre, el logo ni el dominio. Las tecnologías sí se pueden mencionar en la UI de forma descriptiva.

---

## Stack

| Capa | Tecnología |
|---|---|
| Lenguaje | PHP 8.4+ (Pest 5 y PHPUnit 13 lo exigen) |
| Framework | Laravel (la versión estable que instale `laravel new`) |
| UI | Livewire 4 + Blade + Tailwind CSS (Vite) |
| Base de datos | PostgreSQL 16+ |
| Caché y colas | Driver `database` (sin Redis por ahora) |
| HTTP saliente | `Illuminate\Support\Facades\Http` |
| Tests | Pest |
| Estilo de código | Laravel Pint (preset por defecto) |
| CI | GitHub Actions |
| Asistencia IA | Laravel Boost (MCP) para documentación de la versión exacta |
| Login con Steam | Laravel Socialite + `socialiteproviders/steam` (OpenID 2.0) |

No agregar paquetes nuevos sin preguntar antes (los de esta tabla ya están aprobados).

---

## Arquitectura

### 1. Sincronización con PCGamingWiki (job diario)
- **Comando:** `php artisan pcgw:sync`, agendado a diario en `routes/console.php`.
- **Autenticación (desde agosto de 2026):** `cargoquery` exige login con un **bot password** (`action=query&meta=tokens&type=login` + `action=login`), con el permiso *"Create, query and delete data through the Cargo extension"*. Credenciales en `.env` (`PCGW_BOT_USERNAME`, `PCGW_BOT_PASSWORD`); la sesión vive en una cookie y se reintenta el login una vez si expira.
- **Consulta:**
  - Usa la API Cargo (`https://www.pcgamingwiki.com/w/api.php`, `action=cargoquery`).
  - Hace un join de `Game` (antes `Infobox_game`) con `Video` por `_pageID` y pagina con `limit=500` + `offset`. ~55.700 filas ≈ 112 requests.
  - Los campos que empiezan con `_` deben llevar alias (`Game._pageID=PageID`).
- **Campos:** `Video.Upscaling` y `Video.Frame_gen` son listas (delimitador `,`) de tecnologías con versión; `Video.Ray_tracing` es un estado. Se verifican con `action=cargofields&table=Video`. Hay respaldo en `config/pcgw.php`.
- **Guardado:** hace upsert en `games` y `game_steam_appids`. `Steam_AppID` es un campo lista (`115300,42680,42690`): una página puede tener varios AppIDs, y ~20% no tiene ninguno. Si un AppID aparece en dos páginas, gana la última sincronizada.
- **Sync inicial grande:** la wiki tiene decenas de miles de páginas.
  - El sync debe ser **reanudable**: guarda el último offset procesado en la tabla `sync_runs`.
  - Muestra avance en consola (barra de progreso).
  - Acepta `--limit` para desarrollo y `--fresh` para empezar desde cero.
- **Registro de cada ejecución:** inicio, fin, páginas procesadas, errores y estado. La fecha del último sync exitoso se muestra en la UI.
- **Respeto con la wiki:**
  - `User-Agent` con contacto, como pide la wiki: `Renderdex/1.0 (https://github.com/LuisVillagran/renderdex; LuisVillagran) Laravel/13`.
  - Límite oficial: **60 requests/minuto**. Si se excede, responde 429 y bloquea la IP 60 s. Pausa entre requests y reintentos con backoff.
  - **Nunca** consultar la wiki durante una visita de usuario.
  - Contacto con el equipo de PCGW: su Discord.
- **Desarrollo local:** Cloudflare bloquea la API desde la máquina de desarrollo (casa y red móvil), pero responde desde IPs de data center. Para obtener datos reales se usa el workflow manual `.github/workflows/pcgw-sync.yml` ("Run workflow" en GitHub), que corre `php artisan pcgw:sync` con los secrets `PCGW_BOT_USERNAME`/`PCGW_BOT_PASSWORD` del repositorio y publica estadísticas y un volcado (`renderdex-pcgw.sql.gz`) en la rama `pcgw-data`. Para importarlo en local: `php artisan migrate:fresh` y luego `gunzip -c renderdex-pcgw.sql.gz | psql -U postgres -d renderdex`.

### 2. Consulta del usuario
1. Normalizar el input. Formatos aceptados:
   - `steamcommunity.com/profiles/<id64>`
   - `steamcommunity.com/id/<vanity>`
   - SteamID64
   - vanity solo
   
   Las vanity se resuelven con `ISteamUser/ResolveVanityURL`.
2. Pedir los juegos con `IPlayerService/GetOwnedGames` (`include_appinfo=1`, `include_played_free_games=1`).
3. Cachear el resultado por SteamID64 durante 1 hora.
4. Cruzar la biblioteca con la base de datos local (`App\Services\Library\LibraryMatcher`):
   - Primero por Steam AppID (`game_steam_appids`).
   - **Respaldo por nombre** para los que no coinciden: título exacto normalizado (`App\Services\TitleNormalizer`: minúsculas, sin ™ ® ©, apóstrofes/guiones unificados, espacios colapsados) contra `games.normalized_title`, **solo** entre juegos sin ningún AppID en la wiki y **solo** si hay un único candidato. Nunca coincidencias parciales ("Cyberpunk 2077" ≠ "Cyberpunk 2077: Ultimate Edition"). Cubre vacíos de la wiki como Euro Truck Simulator 2.
5. Las portadas se cargan desde el CDN de Steam, con `loading="lazy"` y un fallback si fallan.

### 2b. Inicio de sesión con Steam
- Usa **"Sign in through Steam"** (OpenID 2.0) con Socialite y `socialiteproviders/steam`. La contraseña la escribe el usuario en Steam; Renderdex nunca la ve ni la guarda.
- **Para qué sirve:** solo para identificar al usuario sin que tenga que copiar su URL. Steam devuelve el **SteamID64** y luego se sigue el mismo flujo de la consulta normal (pasos 2 a 5).
- **Límite importante:** iniciar sesión **no** permite leer una biblioteca privada. La Steam Web API sigue respetando la privacidad del perfil. Si los detalles de juegos están privados, se muestra el mismo mensaje de ayuda que en la consulta por URL. Esto debe quedar explicado en la UI junto al botón.
- **Sesión mínima:**
  - Sin tabla `users`. El SteamID64, el nombre visible y el avatar viven solo en la sesión de Laravel.
  - Botón "Cerrar sesión" que destruye la sesión.
  - Al iniciar sesión, redirigir a `/u/{steamid64}`.
- Validar siempre la respuesta OpenID en el servidor: que venga de `steamcommunity.com` y que el `claimed_id` tenga el formato correcto.
  - Implementado en `App\Services\Steam\SteamOpenIdVerifier`: exige `op_endpoint` de Steam, `claimed_id` estricto (`https://steamcommunity.com/openid/id/<SteamID64>`) y firmado, y `return_to` hacia nuestro dominio (`allowed_hosts`). Recién entonces el paquete verifica la firma con Steam (`check_authentication`).
  - **No usar `Socialite::driver('steam')->user()`:** el paquete pide el perfil por `http://` con la API key en la URL. Por eso `services.steam.client_secret` queda en `null`, y el nombre y el avatar se obtienen con `SteamClient::playerSummary()` por HTTPS. Si Steam no responde, se inicia sesión igual, sin nombre ni avatar.
- El botón usa el diseño oficial de "Sign in through Steam" que Valve permite. Es la única excepción a la regla de no usar logos de terceros.
- Ambas opciones conviven en la página de inicio: **"Iniciar sesión con Steam"** como opción principal y el campo de URL/SteamID como alternativa, para quien no quiera iniciar sesión.

### 3. Casos borde (cada uno con su mensaje claro en la UI)
- **Perfil no encontrado:** la vanity no se resuelve o el ID es inválido.
- **Perfil privado:** `GetOwnedGames` vuelve sin `games`.
- **Perfil público con "Detalles del juego" privados:** devuelve el mismo vacío que el caso anterior, así que el mensaje debe explicar ambos casos y cómo cambiar la privacidad.
- **Steam caído o con timeout:** mensaje de reintento. No mostrar errores técnicos.
- **Biblioteca vacía.**
- **Family Sharing:** esos juegos **no** aparecen en `GetOwnedGames`. Avisarlo en una nota de ayuda.
- **Bibliotecas enormes** (miles de juegos): la tabla se pagina. Los filtros y el orden se aplican en el servidor, no en el navegador.
- **Juego sin página en la wiki:** todas sus tecnologías quedan en `Unknown`.

### 4. Clasificación
- Toda la lógica va en `App\Services\FeatureClassifier`, una clase pura sin HTTP ni base de datos, fácil de testear.
- Cada tecnología devuelve un enum `Support`: `Yes`, `No` o `Unknown`.
- Las listas se separan por `,` y se compara **cada valor completo** (sin distinguir mayúsculas ni espacios sobrantes), nunca subcadenas sueltas:

| Tecnología | Campo | Se marca `Yes` si la lista contiene |
|---|---|---|
| DLSS | `Upscaling` | un valor `DLSS <versión>` (p. ej. `DLSS 4.5`) |
| DLSS FG | `Frame_gen` | `DLSS Frame Generation` **o** `DLSS Multi-Frame Generation` (MFG incluye FG) |
| DLSS MFG | `Frame_gen` | `DLSS Multi-Frame Generation` |
| FSR | `Upscaling` | un valor `FSR <versión>` (incluye FSR 1, que es upscaling) |
| FSR FG | `Frame_gen` | `FSR Frame Generation` |
| XeSS | `Upscaling` | un valor `XeSS <versión>` |
| XeSS FG | `Frame_gen` | `XeSS Frame Generation` |
| Ray Tracing | `Ray_tracing` | estado `true`, `limited`, `hackable` o `always on` |

- Valores conocidos que no corresponden a ninguna tecnología de la tabla (`TSR`, `TAAU`, `NIS`, `MetalFX`, `MetalFX Frame Interpolation`) se ignoran para clasificar, pero cuentan para decidir `No`.
- **Cuándo queda `No`:**
  - Upscaling y frame gen: la lista **de ese mismo campo** tiene valores, pero ninguno es esa tecnología (p. ej. `Upscaling = FSR 1` → DLSS `No`).
  - Ray tracing: el estado es `false` o `n/a`.
- **Cuándo queda `Unknown`:** en cualquier otro caso. En particular: lista vacía (la wiki no guarda un estado aparte, así que vacío no significa "no"), RT vacío o `unknown`, sin fila en `Video`, o sin página en la wiki. Un juego con `Upscaling` documentado y `Frame_gen` vacío queda con frame gen `Unknown`, no `No` (decisión tomada: preferir honestidad a menos "?").
- **Cuidado con los falsos positivos:** "FSR 1" o "FSR 3" en `Upscaling` nunca implican FSR FG; frame gen solo sale de `Frame_gen`. Esto se cubre con tests.
- La clasificación se calcula en el sync y se guarda en columnas. No se recalcula en cada request.
- El texto crudo de tecnologías (por ejemplo "DLSS 4, FSR 3.1, XeSS 2") también se guarda, para mostrar versiones en la UI.
- **Mejora futura (no implementar todavía):** una fuente de respaldo para contrastar los juegos que quedan en `Unknown` (por ejemplo, las listas oficiales de NVIDIA, AMD e Intel). Solo como complemento de PCGamingWiki, nunca como fuente principal: no traen Steam AppID y el cruce sería por nombre. Revisar licencia y términos antes de usarlas.

### Estructura sugerida
```
app/
  Console/Commands/SyncPcgw.php
  Enums/Support.php
  Enums/Technology.php            # Dlss, DlssFg, DlssMfg, Fsr, FsrFg, Xess, XessFg, RayTracing (+ vendor())
  Livewire/LibraryLookup.php      # input del perfil
  Livewire/LibraryTable.php       # filtros, búsqueda, orden, paginación
  Models/Game.php
  Models/SyncRun.php
  Services/Steam/SteamClient.php
  Services/Steam/ProfileInputParser.php
  Http/Controllers/SteamAuthController.php   # redirect, callback, logout
  Services/Pcgw/PcgwClient.php
  Services/FeatureClassifier.php
config/pcgw.php
config/services.php               # steam.key
tests/Fixtures/                   # JSON de Steam y PCGW para Http::fake()
.github/workflows/ci.yml
docs/arquitectura.md              # diagrama (Mermaid) + decisiones
```

### Modelo de datos (inicial)
- `games`
  - Identificación: id, pcgw_page_id (unique), title, normalized_title (index, para el respaldo por nombre).
  - Clasificación (enum `Support`): dlss, dlss_fg, dlss_mfg, fsr, fsr_fg, xess, xess_fg, ray_tracing.
  - Texto crudo: upscaling_tech (text), frame_gen_tech (text).
  - Control: synced_at.
- `game_steam_appids`: steam_appid (unique, index), game_id (FK).
- `sync_runs`: id, started_at, finished_at, last_offset, pages_processed, status, error (text).
- La biblioteca del usuario **no se guarda** en la base de datos. Solo vive en caché con TTL.

---

## UI

- **Inicio:** botón "Iniciar sesión con Steam" como opción principal y, debajo, un campo para pegar el perfil, con ayuda breve sobre qué formatos acepta y cómo hacer pública la biblioteca.
- **Resultados:**
  - URL compartible: `/u/{steamid64}`.
  - Estadísticas arriba: total de juegos y cuántos soportan cada tecnología.
  - **Selector "Mi GPU": NVIDIA / AMD / Intel / Todas.** Resalta las columnas de esa marca y preselecciona sus filtros. La elección se recuerda en `localStorage`.
  - Tabla con portada, nombre (enlace a su página en PCGamingWiki), horas jugadas, columnas por tecnología agrupadas por marca y versiones detectadas.
  - Chips de filtro combinables, búsqueda por nombre y orden por nombre u horas.
- **Pie de página:**
  - Crédito a PCGamingWiki con licencia CC BY-NC-SA 3.0.
  - Fecha del último sync.
  - Enlaces a privacidad, al repositorio y a "no afiliado a Valve, NVIDIA, AMD ni Intel".
- **Página de privacidad:** `/privacidad`.
- **Modo oscuro:** según `prefers-color-scheme`.
- **Responsive:** en móvil se ocultan columnas secundarias.
- **Accesibilidad básica:**
  - Contraste suficiente.
  - Íconos ✓/✗/? con texto alternativo o `aria-label` (no depender solo del color).
  - Navegable con teclado.
  - `<label>` en los inputs.
- **Metadatos Open Graph:** título, descripción e imagen para que el link se vea bien al compartirlo.

---

## Reglas del proyecto (no negociables)

- **Sin publicidad ni monetización.** Los datos de PCGamingWiki tienen licencia **CC BY-NC-SA 3.0**: hay que mostrar crédito y enlace a la wiki en el footer y en cada juego.
- **Términos de la Steam Web API:**
  - La `STEAM_API_KEY` vive solo en `.env` y nunca llega al frontend, a los logs ni al repositorio.
  - Nada en la UI puede sugerir afiliación con Valve, Steam, NVIDIA, AMD o Intel. Sin sus logos.
  - Debe existir una **política de privacidad** que explique qué se consulta, que no se almacena, y qué datos guarda la sesión al iniciar con Steam (SteamID64, nombre y avatar, solo mientras dure la sesión).
  - Solo se consultan datos de un perfil cuando el propio usuario lo pide.
  - El límite es de 100.000 llamadas diarias: aplicar **rate limiting por IP** en la ruta de consulta (`RateLimiter`).
- Los tests **nunca** llaman a APIs reales. Usar `Http::fake()` con fixtures JSON en `tests/Fixtures/`.
- No guardar SteamIDs en logs ni en analítica.

---

## Convenciones

- Código, nombres de clases, variables, rutas y commits en **inglés**.
- Textos de la UI en **español**, en archivos de `lang/es/`. Deja todo preparado para agregar `lang/en/` más adelante.
- Comentarios en español, solo donde aporten (el porqué, no el qué).
- Componentes Livewire chicos. La lógica de negocio va en `Services/`, no en los componentes.
- Licencia del código: **MIT** (archivo `LICENSE`). No aplica a los datos de PCGamingWiki.

---

## Calidad y CI

- `.github/workflows/ci.yml` corre en cada push y PR:
  - `composer install`
  - `./vendor/bin/pint --test`
  - `php artisan test`, con un servicio PostgreSQL en el workflow.
- **Tests mínimos esperados:**
  - `FeatureClassifier`: una tabla de casos por tecnología, incluidos los falsos positivos.
  - `ProfileInputParser`: todos los formatos de input y los inválidos.
  - Login con Steam: callback válido, `claimed_id` inválido o de otro dominio, cancelación del usuario, logout.
  - `SteamClient`: respuesta normal, perfil privado, timeout.
  - `SyncPcgw`: paginación, reanudación, AppIDs múltiples.
  - Componentes Livewire: búsqueda, filtros y mensajes de error.

---

## Deploy

**Plataforma por definir antes de la etapa 6.** Las opciones son un VPS (por ejemplo con Laravel Forge o configurado a mano), Laravel Cloud o Railway. No elegir sin consultar.

Requisitos, sea cual sea:
- PHP 8.4+ y PostgreSQL.
- Cron cada minuto para `schedule:run`.
- Un worker de colas permanente.
- HTTPS.
- Variables de entorno: `STEAM_API_KEY`, `PCGW_BOT_USERNAME`, `PCGW_BOT_PASSWORD`, `APP_URL` y base de datos.
- Una IP de salida que PCGamingWiki no bloquee (las de data center funcionan; verificado desde GitHub Actions).
- Ejecutar el sync inicial completo una vez, antes de abrir el sitio.

---

## README (para el portafolio)

El `README.md` del repositorio debe incluir:
- Qué es, con capturas o un GIF del flujo.
- El link a la demo.
- El stack.
- Un diagrama de arquitectura (Mermaid): Steam API y PCGamingWiki → sync → PostgreSQL → Livewire.
- Las decisiones técnicas y por qué:
  - sync diario en vez de consultas en vivo;
  - biblioteca solo en caché;
  - clasificación precalculada.
- Cómo correrlo localmente.
- Créditos y licencias.

---

## Forma de trabajar

- Avanzar **por etapas** y validar cada capa antes de pasar a la siguiente (tests en verde + prueba manual).
- Antes de cambios grandes, proponer un plan corto y esperar confirmación.
- Después de cada cambio: `./vendor/bin/pint` y `php artisan test`.
- Commits chicos y descriptivos, uno por funcionalidad.
- Al cerrar una etapa, actualizar la sección **Estado actual** de este archivo.

---

## Comandos

```bash
composer run dev              # servidor + vite + colas
php artisan migrate
php artisan pcgw:sync         # sincroniza la wiki
php artisan pcgw:sync --limit=200   # para desarrollo
php artisan pcgw:sync --fresh       # sync completo desde cero
php artisan test
./vendor/bin/pint
```

---

## Roadmap

1. **Setup:**
   - Proyecto Laravel con PostgreSQL, Livewire, Pest y Laravel Boost.
   - `.env.example` completo.
   - `LICENSE` MIT.
   - CI en GitHub Actions.
2. **Sync PCGW:**
   - `PcgwClient`, migraciones y enums.
   - `FeatureClassifier` con tests.
   - Comando `pcgw:sync` reanudable, con `sync_runs`.
3. **Steam:**
   - `ProfileInputParser` con tests de todos los formatos.
   - `SteamClient`, caché y manejo de todos los casos borde.
   - Login con Steam (Socialite + OpenID), sesión mínima y logout, con tests.
4. **UI:**
   - Inicio con botón "Iniciar sesión con Steam" + campo de URL alternativo, y resultados.
   - Avatar y nombre del usuario en el header cuando hay sesión.
   - Selector "Mi GPU".
   - Tabla paginada con filtros, búsqueda y orden.
   - Estadísticas y URL compartible.
5. **Pulido:**
   - Privacidad, créditos y rate limiting.
   - Modo oscuro, responsive y accesibilidad.
   - Open Graph y fecha del último sync.
6. **Publicación:**
   - Elegir plataforma y hacer el deploy.
   - Sync inicial completo.
   - README con capturas y diagrama.

## Estado actual

- [x] Etapa 1: Setup
  - Repositorio: https://github.com/LuisVillagran/renderdex (CI en verde).
  - Local: Herd (`http://renderdex.test`) + PostgreSQL 18 (bases `renderdex` y `renderdex_test`).
  - Sin tabla `users`: solo `sessions`, `cache` y `jobs`.
- [x] Etapa 2: Sync PCGW
  - `pcgw:sync` probado contra la wiki real desde GitHub Actions: 55.717 páginas en ~3 min, 55.715 juegos y 62.318 AppIDs (octubre de 2026). Volcado importable en la rama `pcgw-data`.
  - ~12% de los juegos no tiene `Steam_AppID` en la wiki (algunos no están en Steam; otros son vacíos de la wiki, como Euro Truck Simulator 2).
- [ ] Etapa 3: Steam (código y tests listos; login real con Steam probado a mano; falta la prueba manual de la biblioteca con `STEAM_API_KEY`)
- [ ] Etapa 4: UI
- [ ] Etapa 5: Pulido
- [ ] Etapa 6: Publicación

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
