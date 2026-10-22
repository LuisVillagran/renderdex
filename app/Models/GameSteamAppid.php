<?php

namespace App\Models;

use Database\Factories\GameSteamAppidFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSteamAppid extends Model
{
    /** @use HasFactory<GameSteamAppidFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'steam_appid',
        'game_id',
    ];

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'steam_appid' => 'integer',
        ];
    }
}
