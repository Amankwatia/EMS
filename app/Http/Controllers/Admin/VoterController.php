<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\Import;
use App\Models\Voter;
use App\Services\LockedElectionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VoterController extends Controller
{
    public function index(Request $request)
    {
        $voters = Voter::with('election')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('student_id', 'like', '%'.$request->q.'%')
                    ->orWhere('full_name', 'like', '%'.$request->q.'%');
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('has_voted', $request->status === 'voted'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.voters.index', compact('voters'));
    }

    public function create()
    {
        return view('admin.voters.form', [
            'voter' => new Voter(['is_eligible' => true]),
            'elections' => Election::orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request, LockedElectionGuard $lockedElectionGuard)
    {
        $data = $this->validated($request);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));
        $data['pin_hash'] = Hash::make($data['pin']);
        unset($data['pin']);

        Voter::create($data);

        return redirect()->route('admin.voters.index')->with('status', 'Voter created.');
    }

    public function show(Voter $voter)
    {
        return redirect()->route('admin.voters.edit', $voter);
    }

    public function edit(Voter $voter)
    {
        return view('admin.voters.form', [
            'voter' => $voter,
            'elections' => Election::orderByDesc('id')->get(),
        ]);
    }

    public function update(Request $request, Voter $voter, LockedElectionGuard $lockedElectionGuard)
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

    public function destroy(Voter $voter, LockedElectionGuard $lockedElectionGuard)
    {
        $lockedElectionGuard->ensureUnlocked($voter->election);
        abort_if($voter->has_voted, 422, 'A voter who has voted cannot be deleted.');

        $voter->delete();

        return redirect()->route('admin.voters.index')->with('status', 'Voter deleted.');
    }

    public function resetVote(Request $request, Voter $voter, LockedElectionGuard $lockedElectionGuard)
    {
        $lockedElectionGuard->ensureUnlocked($voter->election);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        abort_if(! $voter->has_voted, 422, 'This voter has not voted.');

        $voter->update([
            'has_voted' => false,
            'voted_at' => null,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'election_id' => $voter->election_id,
            'role' => $request->user()->roles->pluck('name')->join(', '),
            'action' => 'voter.vote_status_reset',
            'description' => "Reset voted status for student ID {$voter->student_id}. Reason: {$data['reason']}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'severity' => 'critical',
            'created_at' => now(),
        ]);

        return back()->with('status', 'Voter status reset. Anonymous votes were not edited.');
    }

    public function resetPin(Request $request, Voter $voter, LockedElectionGuard $lockedElectionGuard)
    {
        $lockedElectionGuard->ensureUnlocked($voter->election);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $plainPin = (string) random_int(100000, 999999);
        $voter->update([
            'pin_hash' => Hash::make($plainPin),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'election_id' => $voter->election_id,
            'role' => $request->user()->roles->pluck('name')->join(', '),
            'action' => 'voter.pin_reset',
            'description' => "Reset PIN for student ID {$voter->student_id}. Reason: {$data['reason']}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'severity' => 'warning',
            'created_at' => now(),
        ]);

        return back()->with('generated_pin', [
            'student_id' => $voter->student_id,
            'pin' => $plainPin,
        ])->with('status', 'PIN reset. Show or print this PIN now; it will not be shown again.');
    }

    public function import(Request $request, LockedElectionGuard $lockedElectionGuard)
    {
        $data = $request->validate([
            'election_id' => ['required', 'exists:elections,id'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);
        $lockedElectionGuard->ensureUnlocked(Election::findOrFail($data['election_id']));

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $headers = array_map('trim', fgetcsv($handle) ?: []);
        $required = ['student_id', 'full_name', 'class_name', 'programme', 'house', 'gender', 'pin'];
        abort_if(array_diff($required, $headers), 422, 'CSV is missing one or more required columns.');

        $total = 0;
        $successful = 0;
        $failed = 0;
        $failedRows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $total++;
            $record = array_combine($headers, $row);

            try {
                if (blank($record['student_id'] ?? null) || blank($record['full_name'] ?? null) || blank($record['pin'] ?? null)) {
                    throw new \RuntimeException('Missing student_id, full_name, or pin.');
                }

                Voter::updateOrCreate(
                    ['election_id' => $data['election_id'], 'student_id' => trim($record['student_id'])],
                    [
                        'full_name' => trim($record['full_name']),
                        'class_name' => trim($record['class_name'] ?? ''),
                        'programme' => trim($record['programme'] ?? ''),
                        'house' => trim($record['house'] ?? ''),
                        'gender' => trim($record['gender'] ?? ''),
                        'pin_hash' => Hash::make((string) $record['pin']),
                        'is_eligible' => true,
                    ]
                );
                $successful++;
            } catch (\Throwable $exception) {
                $failed++;
                $failedRows[] = $this->failedImportRow($headers, $row, $exception->getMessage());
            }
        }

        fclose($handle);
        $failedRowsPath = $this->storeFailedRows('voters', $headers, $failedRows);

        Import::create([
            'election_id' => $data['election_id'],
            'import_type' => 'voters',
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
            'action' => 'voters.imported',
            'description' => "Imported {$successful} voters from {$request->file('file')->getClientOriginalName()}. Failed rows: {$failed}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('status', "Imported {$successful} voters. Failed rows: {$failed}.");
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

        $path = 'imports/failed-'.$type.'-'.now()->format('YmdHis').'-'.Str::random(8).'.csv';
        Storage::disk('local')->put($path, $contents);

        return $path;
    }
}
