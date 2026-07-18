<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\Position;
use App\Services\LockedElectionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(): View
    {
        return view('admin.positions.index', [
            'positions' => Position::with('election')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.positions.form', [
            'position' => new Position(['max_choices' => 1, 'is_required' => true, 'is_active' => true]),
            'elections' => Election::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $data = $this->validated($request);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        Position::create($data);

        return redirect()->route('admin.positions.index')->with('status', 'Position created.');
    }

    public function show(Position $position): RedirectResponse
    {
        return redirect()->route('admin.positions.edit', $position);
    }

    public function edit(Position $position): View
    {
        return view('admin.positions.form', [
            'position' => $position,
            'elections' => Election::orderByDesc('id')->get(),
        ]);
    }

    public function update(Request $request, Position $position, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $data = $this->validated($request);
        $lockedElectionGuard->ensureUnlocked($position->election);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        $position->update($data);

        return redirect()->route('admin.positions.index')->with('status', 'Position updated.');
    }

    public function destroy(Position $position, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $lockedElectionGuard->ensureUnlocked($position->election);
        abort_if($position->election->anonymousVotes()->exists(), 422, 'Positions cannot be deleted after votes exist.');

        $position->delete();

        return redirect()->route('admin.positions.index')->with('status', 'Position deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'election_id' => ['required', 'exists:elections,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_choices' => ['required', 'integer', 'min:1', 'max:10'],
            'display_order' => ['required', 'integer', 'min:0'],
            'is_required' => ['boolean'],
            'allow_abstain' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }
}
