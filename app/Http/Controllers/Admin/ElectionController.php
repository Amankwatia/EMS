<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ElectionController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        return view('admin.elections.index', [
            'elections' => Election::query()->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.elections.form', ['election' => new Election(['status' => 'draft'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $election = Election::create($data);
        $this->audit($request, $election, 'election.created', 'Election created.');

        return redirect()->route('admin.elections.index')->with('status', 'Election created.');
    }

    public function show(Election $election): RedirectResponse
    {
        return redirect()->route('admin.elections.edit', $election);
    }

    public function edit(Election $election): View
    {
        return view('admin.elections.form', ['election' => $election]);
    }

    public function readiness(Election $election): View
    {
        $positions = $election->positions()->withCount([
            'candidates as active_candidates_count' => fn ($query) => $query->where('status', 'active'),
        ])->get();

        $checks = collect([
            [
                'label' => 'Election has a title',
                'passed' => filled($election->title),
                'detail' => 'A clear election title appears on ballots and reports.',
            ],
            [
                'label' => 'Election timing is valid',
                'passed' => $election->start_at && $election->end_at && $election->end_at->greaterThan($election->start_at),
                'detail' => 'Set a start and end time before voting begins.',
            ],
            [
                'label' => 'At least one active position exists',
                'passed' => $positions->where('is_active', true)->isNotEmpty(),
                'detail' => 'Voters need at least one active position on the ballot.',
            ],
            [
                'label' => 'Every active position has active candidates',
                'passed' => $positions->where('is_active', true)->every(fn ($position) => $position->active_candidates_count > 0),
                'detail' => 'Positions without active candidates block a complete ballot.',
            ],
            [
                'label' => 'Eligible voters are loaded',
                'passed' => $election->voters()->where('is_eligible', true)->exists(),
                'detail' => 'Import or create eligible voters before opening the election.',
            ],
            [
                'label' => 'Anonymous vote table has no voter identity columns',
                'passed' => collect(['voter_id', 'student_id', 'user_id', 'email', 'name'])->every(fn ($column) => ! Schema::hasColumn('anonymous_votes', $column)),
                'detail' => 'The anonymous vote table must not directly identify voters.',
            ],
        ]);

        return view('admin.elections.readiness', [
            'election' => $election,
            'checks' => $checks,
            'ready' => $checks->every(fn ($check) => $check['passed']),
        ]);
    }

    public function update(Request $request, Election $election): RedirectResponse
    {
        abort_if($election->status === 'locked' && ! $request->user()->can('lock results'), 403);

        $election->update($this->validated($request));
        $this->audit($request, $election, 'election.updated', 'Election updated.');

        return redirect()->route('admin.elections.index')->with('status', 'Election updated.');
    }

    public function destroy(Election $election): RedirectResponse
    {
        abort_if($election->anonymousVotes()->exists(), 422, 'An election with votes cannot be deleted.');

        $election->delete();

        return redirect()->route('admin.elections.index')->with('status', 'Election deleted.');
    }

    public function status(Request $request, Election $election): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'scheduled', 'active', 'paused', 'closed', 'published', 'locked'])],
            'lock_reason' => ['nullable', 'required_if:status,locked', 'string', 'max:1000'],
        ]);

        abort_if($data['status'] === 'locked' && ! $request->user()->can('lock results'), 403);
        abort_if($election->status === 'locked' && ! $request->user()->can('lock results'), 403);
        abort_if($data['status'] === 'published' && ! $request->user()->can('publish results'), 403);

        if ($election->status === 'locked' && $data['status'] !== 'locked' && blank($data['lock_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'lock_reason' => 'A reason is required to unlock a locked election.',
            ]);
        }

        if ($data['status'] === 'closed') {
            $data['closed_by'] = $request->user()->id;
            $data['closed_at'] = now();
        }

        if ($data['status'] === 'locked') {
            $data['locked_by'] = $request->user()->id;
            $data['locked_at'] = now();
        }

        $election->update($data);
        $this->audit($request, $election, 'election.status_changed', "Election status changed to {$data['status']}.");

        return back()->with('status', 'Election status updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'academic_year' => ['nullable', 'string', 'max:255'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'active', 'paused', 'closed', 'published', 'locked'])],
            'results_visible_to_public' => ['boolean'],
            'allow_internal_live_preview' => ['boolean'],
        ]);
    }

    private function audit(Request $request, Election $election, string $action, string $description): void
    {
        $this->auditLogger->record($request, $action, $description, $election->id);
    }
}
