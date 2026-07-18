@extends('layouts.admin')

@section('title', 'Results')

@section('content')
<div class="mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <div>
            <h2 class="h4">{{ $election->title }}</h2>
            <p class="text-muted mb-0">Status: {{ ucfirst($election->status) }}</p>
        </div>
        @can('view turnout')
            <a class="btn btn-outline-success btn-sm" href="{{ route('admin.participation.index', ['election_id' => $election->id]) }}">View Voter Participation</a>
        @endcan
        @can('export reports')
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.reports.results.pdf', $election) }}">PDF Results</a>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.reports.turnout.pdf', $election) }}">PDF Turnout</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.exports.results.csv', $election) }}">CSV Results</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.exports.voters.csv', $election) }}">CSV Voters</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.exports.voted-status.csv', $election) }}">CSV Voted Status</a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.exports.candidates.csv', $election) }}">CSV Candidates</a>
            </div>
        @endcan
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Registered Voters</div>
            <div class="h3 mb-0">{{ $registeredVoters }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Completed Ballots</div>
            <div class="h3 mb-0">{{ $votersCompleted }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Turnout</div>
            <div class="h3 mb-0">{{ $turnout }}%</div>
        </div></div>
    </div>
</div>

@foreach ($positions as $position)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="h5 mb-0">{{ $position->name }}</h3>
                @if ($results[$position->id]['isTie'])
                    <span class="badge bg-warning text-dark">Tie</span>
                @elseif ($results[$position->id]['winner'])
                    <span class="badge bg-success">Winner: {{ $results[$position->id]['winner']->candidate_name }}</span>
                @endif
            </div>
            <div class="table-responsive mt-3">
                <table class="table">
                    <thead><tr><th>Candidate</th><th class="text-end">Votes</th><th class="text-end">Percent</th></tr></thead>
                    <tbody>
                    @foreach ($results[$position->id]['candidateCounts'] as $row)
                        <tr>
                            <td>{{ $row['candidate']->candidate_name }}</td>
                            <td class="text-end">{{ $row['votes'] }}</td>
                            <td class="text-end">{{ $row['percentage'] }}%</td>
                        </tr>
                    @endforeach
                    @if ($position->allow_abstain)
                        <tr>
                            <td>Abstentions</td>
                            <td class="text-end">{{ $results[$position->id]['abstentions'] }}</td>
                            <td class="text-end">{{ $results[$position->id]['total'] > 0 ? round(($results[$position->id]['abstentions'] / $results[$position->id]['total']) * 100, 1) : 0 }}%</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
@endsection
