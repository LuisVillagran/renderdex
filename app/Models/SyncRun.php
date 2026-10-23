<?php

namespace App\Models;

use App\Enums\SyncStatus;
use Carbon\CarbonInterface;
use Database\Factories\SyncRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    /** @use HasFactory<SyncRunFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'last_offset',
        'pages_processed',
        'error',
        'started_at',
        'finished_at',
    ];

    public static function lastSuccessfulAt(): ?CarbonInterface
    {
        return static::where('status', SyncStatus::Completed)->latest('finished_at')->value('finished_at');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SyncStatus::class,
            'last_offset' => 'integer',
            'pages_processed' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
