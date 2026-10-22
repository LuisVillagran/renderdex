"""Descarga muestras de la API Cargo de PCGamingWiki para diseñar el sync y sus fixtures.

Puente temporal de desarrollo: la wiki bloquea las conexiones desde la máquina local,
pero responde desde GitHub Actions. Se borra cuando exista `php artisan pcgw:sync`.

Uso: python3 pcgw_sample.py <directorio-salida>
"""

import http.cookiejar
import json
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

API = "https://www.pcgamingwiki.com/w/api.php"
USER_AGENT = "Renderdex/1.0 (https://github.com/LuisVillagran/renderdex; LuisVillagran) Python-urllib"
# La wiki permite 60 requests/minuto y bloquea la IP 60 s si se excede.
PAUSE_SECONDS = 1.5
PAGE_SIZE = 500

out_dir = sys.argv[1]
os.makedirs(out_dir, exist_ok=True)
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
summary = {"requests": 0, "errors": [], "files": []}


def call(params, post=False):
    encoded = urllib.parse.urlencode({**params, "format": "json"})
    if post:
        request = urllib.request.Request(API, data=encoded.encode(), headers={"User-Agent": USER_AGENT})
    else:
        request = urllib.request.Request(f"{API}?{encoded}", headers={"User-Agent": USER_AGENT})
    time.sleep(PAUSE_SECONDS)
    summary["requests"] += 1
    with opener.open(request, timeout=60) as response:
        return json.load(response)


def save(name, data):
    with open(os.path.join(out_dir, f"{name}.json"), "w", encoding="utf-8") as handle:
        json.dump(data, handle, ensure_ascii=False, indent=2)
    summary["files"].append(f"{name}.json")


def cargo(name, pages=1, **params):
    """Ejecuta un cargoquery (paginado si pages > 1) y guarda las respuestas crudas."""
    rows, raw = [], []
    try:
        for page in range(pages):
            response = call({"action": "cargoquery", "limit": PAGE_SIZE, "offset": page * PAGE_SIZE, **params})
            raw.append(response)
            if "error" in response:
                summary["errors"].append({"query": name, "error": response["error"]})
                break
            batch = [item["title"] for item in response.get("cargoquery", [])]
            rows.extend(batch)
            if len(batch) < PAGE_SIZE:
                break
    except urllib.error.HTTPError as error:
        summary["errors"].append({"query": name, "http": error.code})
    save(name, {"params": params, "row_count": len(rows), "rows": rows, "raw_first_page": raw[:1]})


def finish(exit_code):
    save("_summary", summary)
    with open(os.path.join(out_dir, "README.md"), "w", encoding="utf-8") as handle:
        handle.write(
            "# Muestras de PCGamingWiki\n\n"
            "Datos obtenidos de [PCGamingWiki](https://www.pcgamingwiki.com/) mediante su API Cargo, "
            "bajo licencia [CC BY-NC-SA 3.0](https://creativecommons.org/licenses/by-nc-sa/3.0/).\n\n"
            "Generado automáticamente por `.github/workflows/pcgw-sample.yml` para el desarrollo de Renderdex.\n"
        )
    sys.exit(exit_code)


# 1. Login con bot password (obligatorio para cargoquery desde agosto de 2026).
try:
    token = call({"action": "query", "meta": "tokens", "type": "login"})["query"]["tokens"]["logintoken"]
    login = call(
        {
            "action": "login",
            "lgname": os.environ["PCGW_BOT_USERNAME"],
            "lgpassword": os.environ["PCGW_BOT_PASSWORD"],
            "lgtoken": token,
        },
        post=True,
    )["login"]
except (urllib.error.HTTPError, KeyError) as error:
    summary["errors"].append({"step": "login", "error": repr(error)})
    finish(1)

summary["login"] = {"result": login.get("result"), "reason": login.get("reason")}
if login.get("result") != "Success":
    finish(1)

# 2. Estructura de las tablas.
for table in ("Game", "Video"):
    try:
        save(f"fields_{table}", call({"action": "cargofields", "table": table}))
    except urllib.error.HTTPError as error:
        summary["errors"].append({"query": f"fields_{table}", "http": error.code})

join = {"tables": "Game,Video", "join_on": "Game._pageID=Video._pageID"}
row_fields = (
    "Game._pageID=PageID,Game._pageName=Page,Game.Steam_AppID=SteamAppID,"
    "Video.Upscaling=Upscaling,Video.Frame_gen=FrameGen,Video.Ray_tracing=RayTracing"
)

# 3. Tamaños totales.
cargo("count_game_video", **join, fields="COUNT(*)=Total")
cargo("count_video", tables="Video", fields="COUNT(*)=Total")

# 4. Vocabulario: valores distintos de cada campo relevante.
cargo("distinct_ray_tracing", tables="Video", fields="Video.Ray_tracing=Value,COUNT(*)=N", group_by="Video.Ray_tracing")
for field in ("Upscaling", "Frame_gen"):
    cargo(
        f"distinct_{field.lower()}",
        pages=4,
        tables=f"Video__{field}",
        fields="_value=Value,COUNT(*)=N",
        group_by="_value",
        order_by="N DESC",
    )

# 5. Filas de ejemplo para fixtures.
cargo("sample_first_rows", pages=2, **join, fields=row_fields, order_by="Game._pageID")
cargo("sample_with_upscaling", **join, fields=row_fields, where='Video.Upscaling HOLDS LIKE "%"', order_by="Game._pageID")
cargo("sample_with_frame_gen", **join, fields=row_fields, where='Video.Frame_gen HOLDS LIKE "%"', order_by="Game._pageID")
cargo("sample_multiple_appids", **join, fields=row_fields, where='Game.Steam_AppID LIKE "%,%"', order_by="Game._pageID")

finish(0)
