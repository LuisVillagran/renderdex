<?php

namespace App\Models;

use App\Enums\Support;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pcgw_page_id',
        'title',
        'normalized_title',
        'search_title',
        'dlss',
        'dlss_fg',
        'dlss_mfg',
        'fsr',
        'fsr_fg',
        'xess',
        'xess_fg',
        'ray_tracing',
        'upscaling_tech',
        'frame_gen_tech',
        'synced_at',
    ];

    /**
     * @return HasMany<GameSteamAppid, $this>
     */
    public function steamAppids(): HasMany
    {
        return $this->hasMany(GameSteamAppid::class);
    }

    /**
     * @return HasMany<GameChange, $this>
     */
    public function changes(): HasMany
    {
        return $this->hasMany(GameChange::class);
    }

    /**
     * Clave de la ficha: "<pcgw_page_id>-<slug>". Se usa el ID de la wiki y no el nuestro
     * porque sobrevive a un `pcgw:sync --fresh` sobre una base vacía.
     */
    public function routeKey(): string
    {
        $slug = Str::slug($this->title);

        return $slug === '' ? (string) $this->pcgw_page_id : "{$this->pcgw_page_id}-{$slug}";
    }

    public function url(): string
    {
        return route('games.show', $this->routeKey());
    }

    public function wikiUrl(): string
    {
        return "https://www.pcgamingwiki.com/w/index.php?curid={$this->pcgw_page_id}";
    }

    public function wikiEditUrl(): string
    {
        return "https://www.pcgamingwiki.com/w/index.php?curid={$this->pcgw_page_id}&action=edit";
    }

    /**
     * El primer AppID que lista la wiki, el que se usa para la portada y la tienda.
     */
    public function primarySteamAppid(): ?int
    {
        return $this->steamAppids->sortBy('id')->first()?->steam_appid;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pcgw_page_id' => 'integer',
            'dlss' => Support::class,
            'dlss_fg' => Support::class,
            'dlss_mfg' => Support::class,
            'fsr' => Support::class,
            'fsr_fg' => Support::class,
            'xess' => Support::class,
            'xess_fg' => Support::class,
            'ray_tracing' => Support::class,
            'has_data' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }
}
