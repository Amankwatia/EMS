<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CsvImportRequest;
use App\Models\Election;
use App\Models\Voter;
use App\Services\AuditLogger;
use App\Services\LockedElectionGuard;
use App\Services\VoterImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoterController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['voted', 'not_voted'])],
        ]);
        $search = trim($filters['q'] ?? '');

        $voters = Voter::with('election')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('student_id', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%');
            }))
            ->when(isset($filters['status']), fn ($query) => $query->where('has_voted', $filters['status'] === 'voted'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.voters.index', compact('voters'));
    }

    public function create(): View
    {
        return view('admin.voters.form', [
            'voter' => new Voter(['is_eligible' => true]),
            'elections' => Election::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $data = $this->validated($request);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));
        $data['pin_hash'] = Hash::make($data['pin']);
        unset($data['pin']);

        Voter::create($data);

        return redirect()->route('admin.voters.index')->with('status', 'Voter created.');
    }

    public function show(Voter $voter): RedirectResponse
    {
        return redirect()->route('admin.voters.edit', $voter);
    }

    public function edit(Voter $voter): View
    {
        return view('admin.voters.form', [
            'voter' => $voter,
            'elections' => Election::orderByDesc('id')->get(),
        ]);
    }

    public function update(Request $request, Voter $voter, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $data = $this->validated($request, $voter);
        $lockedElectionGuard->ensureUnlocked($voter->election);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        if (! empty($data['pin'])) {
            $data['pin_hash'] = Hash::make($data['pin']);
        }

        unset($data['pin']);
        $voter->update($data);

        return redirect()->route('admin.voters.index')->with('status', 'Voter updated.');
    }

    public function destroy(Voter $voter, LockedElectionGuard $lockedElectionGuard): RedirectResponse
    {
        $lockedElectionGuard->ensureUnlocked($voter->election);
        abort_if($voter->has_voted, 422, 'A voter who has voted cannot be deleted.');

        $voter->delete();

        return redirect()->route('admin.voters.index')->with('status', 'Voter deleted.');
    }

    public function resetVote(
        Request $request,
        Voter $voter,
        LockedElectionGuard $lockedElectionGuard,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $lockedElectionGuard->ensureUnlocked($voter->election);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        abort_if(! $voter->has_voted, 422, 'This voter has not voted.');

        $voter->update([
            'has_voted' => false,
            'voted_at' => null,
        ]);

        $auditLogger->record(
            $request,
            'voter.vote_status_reset',
            "Reset voted status for student ID {$voter->student_id}. Reason: {$data['reason']}",
            $voter->election_id,
            'critical',
        );

        return back()->with('status', 'Voter status reset. Anonymous votes were not edited.');
    }

    public function resetPin(
        Request $request,
        Voter $voter,
        LockedElectionGuard $lockedElectionGuard,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $lockedElectionGuard->ensureUnlocked($voter->election);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $plainPin = (string) random_int(100000, 999999);
        $voter->update([
            'pin_hash' => Hash::make($plainPin),
        ]);

        $auditLogger->record(
            $request,
            'voter.pin_reset',
            "Reset PIN for student ID {$voter->student_id}. Reason: {$data['reason']}",
            $voter->election_id,
            'warning',
        );

        return back()->with('generated_pin', [
            'student_id' => $voter->student_id,
            'pin' => $plainPin,
        ])->with('status', 'PIN reset. Show or print this PIN now; it will not be shown again.');
    }

    public function import(
        CsvImportRequest $request,
        LockedElectionGuard $lockedElectionGuard,
        VoterImportService $importService,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $election = $request->election();
        $lockedElectionGuard->ensureUnlocked($election);
        $import = $importService->import($election, $request->csvFile(), $request->user());

        $auditLogger->record(
            $request,
            'voters.imported',
            "Imported {$import->successful_rows} voters from {$import->filename}. Failed rows: {$import->failed_rows}.",
            $election->id,
        );

        $status = "Imported {$import->successful_rows} voters. Failed rows: {$import->failed_rows}.";

        if ($import->generated_pins_path) {
            $status .= ' Download the generated PIN report within 24 hours.';
        }

        return back()
            ->with('status', $status)
            ->with('generated_pins_import_id', $import->generated_pins_path ? $import->id : null);
    }

    private function validated(Request $request, ?Voter $voter = null): array
    {
        return $request->validate([
            'election_id' => ['required', 'exists:elections,id'],
            'student_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('voters')->where('election_id', $request->integer('election_id'))->ignore($voter),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'class_name' => ['nullable', 'string', 'max:255'],
            'programme' => ['nullable', 'string', 'max:255'],
            'house' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:255'],
            'pin' => [$voter ? 'nullable' : 'required', 'string', 'min:4', 'max:255'],
            'is_eligible' => ['boolean'],
        ]);
    }
}
