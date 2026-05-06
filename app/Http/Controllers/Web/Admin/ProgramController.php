<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StoreProgramRequest;
use App\Http\Requests\Web\Admin\UpdateProgramRequest;
use App\Models\Channel;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('admin.programs.index', [
            'programs' => Program::query()->with('channel.category')->orderByDesc('start_time')->paginate(24),
            'channels' => Channel::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        Program::query()->create($request->validated());

        return back()->with('status', 'Program created.');
    }

    public function edit(Program $program): View
    {
        return view('admin.programs.edit', [
            'program' => $program,
            'channels' => Channel::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        return redirect()->route('admin.programs.index')->with('status', 'Program updated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $program->delete();

        return back()->with('status', 'Program deleted.');
    }
}
