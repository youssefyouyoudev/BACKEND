<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\UpdateMatchResultRequest;
use App\Models\WorldCupMatch;
use App\Services\WorldCupKnockoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatchResultController extends Controller
{
    public function __construct(private readonly WorldCupKnockoutService $knockoutService) {}

    public function edit(WorldCupMatch $worldCupMatch): View
    {
        abort_unless($worldCupMatch->is_knockout || ((int) $worldCupMatch->match_number >= 73 && (int) $worldCupMatch->match_number <= 104), 404);

        return view('admin.world-cup-matches.result', [
            'worldCupMatch' => $worldCupMatch,
            'resultStatuses' => WorldCupMatch::RESULT_STATUSES,
            'nextWinnerMatch' => $this->knockoutService->nextWinnerMatch($worldCupMatch),
        ]);
    }

    public function update(UpdateMatchResultRequest $request, WorldCupMatch $worldCupMatch): RedirectResponse
    {
        $this->knockoutService->saveResult($worldCupMatch, $request->validated());

        return back()->with('status', __('Winner saved and advanced to next round.'));
    }
}
