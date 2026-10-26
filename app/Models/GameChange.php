<?php

namespace App\Models;

use App\Enums\ChangeType;
use App\Enums\Technology;
use Database\Factories\GameChangeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un cambio que el sync detectó en la wiki. Es lo que la wiki documentó ese día,
 * no necesariamente cuando el juego recibió la tecnología.
 */
class GameChange extends Model
{
    /** @use HasFactory<GameChangeFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'technology',
        'type',
        'previous_version',
        'version',
        'detected_at',
    ];

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeRecent(Builder $query, int $days = 7): void
    {
        $query->where('detected_at', '>=', now()->subDays($days));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'technology' => Technology::class,
            'type' => ChangeType::class,
            'detected_at' => 'datetime',
        ];
    }
}
