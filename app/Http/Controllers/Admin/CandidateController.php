<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Import;
use App\Models\Position;
use App\Services\LockedElectionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CandidateController extends Controller
{
    public function index()
    {
        return view('admin.candidates.index', [
            'candidates' => Candidate::with(['election', 'position'])->orderByDesc('id')->paginate(20),
            'elections' => Election::orderByDesc('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.candidates.form', [
            'candidate' => new Candidate(['status' => 'active']),
            'elections' => Election::orderByDesc('id')->get(),
            'positions' => Position::orderBy('display_order')->get(),
        ]);
    }

    public function store(Request $request, LockedElectionGuard $lockedElectionGuard)
    {
        $data = $this->validated($request);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        Candidate::create($data);

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate created.');
    }

    public function show(Candidate $candidate)
    {
        return redirect()->route('admin.candidates.edit', $candidate);
    }

    public function edit(Candidate $candidate)
    {
        return view('admin.candidates.form', [
            'candidate' => $candidate,
            'elections' => Election::orderByDesc('id')->get(),
            'positions' => Position::orderBy('display_order')->get(),
        ]);
    }

    public function update(Request $request, Candidate $candidate, LockedElectionGuard $lockedElectionGuard)
    {
        $data = $this->validated($request);
        $lockedElectionGuard->ensureUnlocked($candidate->election);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        $candidate->update($data);

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate updated.');
    }

    public function destroy(Candidate $candidate, LockedElectionGuard $lockedElectionGuard)
    {
        $lockedElectionGuard->ensureUnlocked($candidate->election);
        abort_if($candidate->election->anonymousVotes()->exists(), 422, 'Candidates cannot be deleted after votes exist.');

        $candidate->delete();

        return redirect()->route('admin.candidates.index')->with('status', 'Candidate deleted.');
    }

    public function import(Request $request, LockedElectionGuard $lockedElectionGuard)
    {
        $data = $request->validate([
            'election_id' => ['required', 'exists:elections,id'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle) ?: []);
        $required = ['position', 'candidate_name', 'student_id', 'class_name', 'programme', 'house', 'gender', 'manifesto'];
        abort_if(array_diff($required, $headers), 422, 'CSV is missing one or more required columns.');

        $positions = Position::where('election_id', $data['election_id'])->get()->keyBy(fn (Position $position) => mb_strtolower($position->name));
        $total = 0;
        $successful = 0;
        $failed = 0;
        $failedRows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            $record = array_combine($headers, $row);
            $position = $positions->get(mb_strtolower(trim($record['position'] ?? '')));

            if (! $position || blank($record['candidate_name'] ?? null)) {
                $failed++;
                $failedRows[] = $this->failedImportRow($headers, $row, ! $position ? 'Position not found for selected election.' : 'Missing candidate_name.');
                continue;
            }

            try {
                Candidate::updateOrCreate(
                    [
                        'election_id' => $data['election_id'],
                        'position_id' => $position->id,
                        'student_id' => filled($record['student_id'] ?? null) ? trim($record['student_id']) : null,
                        'candidate_name' => trim($record['candidate_name']),
                    ],
                    [
                        'class_name' => trim($record['class_name'] ?? ''),
                        'programme' => trim($record['programme'] ?? ''),
                        'house' => trim($record['house'] ?? ''),
                        'gender' => trim($record['gender'] ?? ''),
                        'manifesto' => trim($record['manifesto'] ?? ''),
                        'status' => 'active',
                    ]
                );
                $successful++;
            } catch (\Throwable $exception) {
                $failed++;
                $failedRows[] = $this->failedImportRow($headers, $row, $exception->getMessage());
            }
        }

        fclose($handle);
        $failedRowsPath = $this->storeFailedRows('candidates', $headers, $failedRows);

        Import::create([
            'election_id' => $data['election_id'],
            'import_type' => 'candidates',
            'filename' => $request->file('file')->getClientOriginalName(),
            'total_rows' => $total,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'failed_rows_path' => $failedRowsPath,
            'imported_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'election_id' => $data['election_id'],
            'role' => $request->user()->roles->pluck('name')->join(', '),
            'action' => 'candidates.imported',
            'description' => "Imported {$successful} candidates from {$request->file('file')->getClientOriginalName()}. Failed rows: {$failed}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('status', "Imported {$successful} candidates. Failed rows: {$failed}.");
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

    private function failedImportRow(array $headers, array $row, string $reason): array
    {
        $values = array_pad($row, count($headers), '');
        $record = array_combine($headers, array_slice($values, 0, count($headers)));
        $record['failure_reason'] = $reason;

        return $record;
    }

    private function storeFailedRows(string $type, array $headers, array $failedRows): ?string
    {
        if ($failedRows === []) {
            return null;
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [...$headers, 'failure_reason']);

        foreach ($failedRows as $row) {
            fputcsv($handle, array_map(fn ($header) => $row[$header] ?? '', [...$headers, 'failure_reason']));
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        fclose($handle);

        $path = 'imports/failed-'.$type.'-'.now()->format('YmdHis').'-'.str()->random(8).'.csv';
        Storage::disk('local')->put($path, $contents);

        return $path;
    }
}
