<?php

namespace App\Models;

use App\Enums\Support;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
