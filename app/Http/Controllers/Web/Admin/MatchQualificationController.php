<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\UpdateMatchQualificationRequest;
use App\Models\WorldCupMatch;
use App\Services\WorldCupKnockoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatchQualificationController extends Controller
{
    public function __construct(private readonly WorldCupKnockoutService $knockoutService) {}

    public function edit(WorldCupMatch $worldCupMatch): View
    {
        abort_unless($worldCupMatch->isKnockout(), 404);

        return view('admin.world-cup-matches.qualify', [
            'worldCupMatch' => $worldCupMatch,
        ]);
    }

    public function update(UpdateMatchQualificationRequest $request, WorldCupMatch $worldCupMatch): RedirectResponse
    {
        $this->knockoutService->qualifyTeam($worldCupMatch, $request->validated('side'));

        return back()->with('status', __('Qualified team saved and advanced to the next round.'));
    }
}
