<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\Voter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ParticipationController extends Controller
{
    public function __invoke(Request $request)
    {
        $elections = Election::query()->orderByDesc('id')->get();
        $election = $request->filled('election_id')
            ? $elections->firstWhere('id', (int) $request->integer('election_id'))
            : $elections->first();

        abort_if($request->filled('election_id') && ! $election, 404);

        if (! $election) {
            return view('admin.participation.index', compact('elections', 'election'));
        }

        $search = trim((string) $request->input('q'));
        $baseQuery = Voter::query()
            ->where('election_id', $election->id)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('student_id', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhere('class_name', 'like', '%'.$search.'%')
                    ->orWhere('programme', 'like', '%'.$search.'%');
            }));

        $votedStudents = (clone $baseQuery)
            ->where('has_voted', true)
            ->orderByDesc('voted_at')
            ->orderBy('student_id')
            ->paginate(15, ['*'], 'voted_page')
            ->withQueryString();

        $notVotedStudents = (clone $baseQuery)
            ->where('has_voted', false)
            ->orderByDesc('is_eligible')
            ->orderBy('student_id')
            ->paginate(15, ['*'], 'not_voted_page')
            ->withQueryString();

        $registered = $election->voters()->count();
        $eligible = $election->voters()->where('is_eligible', true)->count();
        $voted = $election->voters()->where('has_voted', true)->count();
        $notVoted = $registered - $voted;
        $turnout = $eligible > 0 ? round(($voted / $eligible) * 100, 1) : 0;

        AuditLog::create([
            'user_id' => $request->user()->id,
            'election_id' => $election->id,
            'role' => $request->user()->roles->pluck('name')->join(', '),
            'action' => 'voter.participation_viewed',
            'description' => "Viewed voter participation details for {$election->title}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return view('admin.participation.index', compact(
            'elections',
            'election',
            'votedStudents',
            'notVotedStudents',
            'registered',
            'eligible',
            'voted',
            'notVoted',
            'turnout',
        ));
    }
}
