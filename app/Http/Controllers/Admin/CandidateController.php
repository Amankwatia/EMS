<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CsvImportRequest;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Services\AuditLogger;
use App\Services\CandidateImportService;
use App\Services\LockedElectionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CandidateController extends Controller
{
    public function index(): View
    {
        return view('admin.candidates.index', [
            'candidates' => Candidate::with(['election', 'position'])->orderByDesc('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.candidates.form', [
            'candidate' => new Candidate(['status' => 'active']),
            'elections' => Election::orderByDesc('id')->get(),
            'positions' => Position::orderBy('display_order')->get(),
        ]);
    }

    public function store(Request $request, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $data = $this->validated($request);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        Candidate::create($data);

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate created.');
    }

    public function show(Candidate $candidate): RedirectResponse
    {
        return redirect()->route('admin.candidates.edit', $candidate);
    }

    public function edit(Candidate $candidate): View
    {
        return view('admin.candidates.form', [
            'candidate' => $candidate,
            'elections' => Election::orderByDesc('id')->get(),
            'positions' => Position::orderBy('display_order')->get(),
        ]);
    }

    public function update(Request $request, Candidate $candidate, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $data = $this->validated($request);
        $lockedElectionGuard->ensureUnlocked($candidate->election);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        $candidate->update($data);

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate updated.');
    }

    public function destroy(Candidate $candidate, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $lockedElectionGuard->ensureUnlocked($candidate->election);
        abort_if($candidate->election->anonymousVotes()->exists(), 422, 'Candidates cannot be deleted after votes exist.');

        $candidate->delete();

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate deleted.');
    }

    public function import(
        CsvImportRequest $request,
        LockedElectionGuard $lockedElectionGuard,
        CandidateImportService $importService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $election = $request->election();
        $lockedElectionGuard->ensureUnlocked($election);
        $import = $importService->import($election, $request->csvFile(), $request->user());

        $auditLogger->record(
            $request,
            'candidates.imported',
            "Imported {$import->successful_rows} candidates from {$import->filename}. Failed rows: {$import->failed_rows}.",
            $election->id,
        );

        return back()->with('status', "Imported {$import->successful_rows} candidates. Failed rows: {$import->failed_rows}.");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'election_id' => ['required', 'exists:elections,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'candidate_name' => ['required', 'string', 'max:255'],
            'student_id' => ['nullable', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'programme' => ['nullable', 'string', 'max:255'],
            'house' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'manifesto' => ['nullable', 'string'],
            'ballot_number' => ['nullable', 'string', 'max:255'],
            'display_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive', 'disqualified'])],
        ]);

        $position = Position::findOrFail($data['position_id']);
        abort_if((int) $position->election_id !== (int) $data['election_id'], 422, 'Candidate position must belong to the selected election.');

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('candidate-photos', 'public');
        }

        unset($data['photo']);

        return $data;
    }
}
