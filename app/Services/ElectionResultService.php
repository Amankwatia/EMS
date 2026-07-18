<?php

namespace App\Services;

use App\Models\AnonymousVote;
use App\Models\Election;
use App\Models\Voter;

class ElectionResultService
{
    public function payload(Election $election): array
    {
        $registeredVoters = Voter::where('election_id', $election->id)->count();
        $votersCompleted = Voter::where('election_id', $election->id)->where('has_voted', true)->count();
        $turnout = $registeredVoters > 0 ? round(($votersCompleted / $registeredVoters) * 100, 1) : 0;
        $positions = $election->positions()->with('candidates')->get();
        $results = [];

        foreach ($positions as $position) {
            $counts = AnonymousVote::query()
                ->selectRaw('candidate_id, is_abstain, count(*) as total')
                ->where('election_id', $election->id)
                ->where('position_id', $position->id)
                ->groupBy('candidate_id', 'is_abstain')
                ->get();
            $positionTotal = (int) $counts->sum('total');

            $candidateCounts = $position->candidates->map(function ($candidate) use ($counts, $positionTotal) {
                $votes = (int) ($counts->firstWhere('candidate_id', $candidate->id)->total ?? 0);

                return [
                    'candidate' => $candidate,
                    'votes' => $votes,
                    'percentage' => $positionTotal > 0 ? round(($votes / $positionTotal) * 100, 1) : 0,
                ];
            })->sortByDesc('votes')->values();

            $top = $candidateCounts->first()['votes'] ?? 0;
            $leaders = $candidateCounts->where('votes', $top)->where('votes', '>', 0);

            $results[$position->id] = [
                'candidateCounts' => $candidateCounts,
                'abstentions' => (int) ($counts->firstWhere('is_abstain', true)->total ?? 0),
                'total' => $positionTotal,
                'winner' => $leaders->count() === 1 ? $leaders->first()['candidate'] : null,
                'isTie' => $leaders->count() > 1,
            ];
        }

        return compact('election', 'positions', 'registeredVoters', 'votersCompleted', 'turnout', 'results');
    }
}
