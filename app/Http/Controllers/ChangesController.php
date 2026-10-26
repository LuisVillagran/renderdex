<?php

namespace App\Http\Controllers;

use App\Enums\Capability;
use App\Enums\Technology;
use App\Models\GameChange;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Lo que la wiki documentó de nuevo en cada sync: tecnologías agregadas, quitadas o actualizadas.
 */
class ChangesController extends Controller
{
    public const PER_PAGE = 60;

    public const FEED_SIZE = 50;

    public function index(Request $request): View
    {
        $capability = Capability::tryFrom((string) $request->query('cap'));

        return view('changes.index', [
            'capability' => $capability,
            'changes' => $this->changes($capability)->paginate(self::PER_PAGE)->withQueryString(),
        ]);
    }

    public function feed(): Response
    {
        return response()
            ->view('changes.feed', ['changes' => $this->changes(null)->limit(self::FEED_SIZE)->get()])
            ->header('Content-Type', 'application/atom+xml; charset=UTF-8');
    }

    /**
     * @return Builder<GameChange>
     */
    private function changes(?Capability $capability): Builder
    {
        return GameChange::query()
            ->with('game')
            ->when($capability, fn (Builder $changes) => $changes->whereIn(
                'technology',
                array_map(fn (Technology $technology) => $technology->value, $capability->technologies()),
            ))
            ->orderByDesc('detected_at')
            ->orderBy('game_id')
            ->orderBy('id');
    }
}
