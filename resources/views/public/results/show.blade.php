@extends('layouts.voter')

@section('content')
<div class="voter-card mx-auto">
    <div class="text-center mb-4">
        <h1 class="h3">{{ $election->title }}</h1>
        <p class="text-muted mb-0">Published Results</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm"><div class="card-body text-center">
                <div class="text-muted small">Registered</div>
                <div class="h4 mb-0">{{ $registeredVoters }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm"><div class="card-body text-center">
                <div class="text-muted small">Completed</div>
                <div class="h4 mb-0">{{ $votersCompleted }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm"><div class="card-body text-center">
                <div class="text-muted small">Turnout</div>
                <div class="h4 mb-0">{{ $turnout }}%</div>
            </div></div>
        </div>
    </div>

    @foreach ($positions as $position)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">{{ $position->name }}</h2>
                    @if ($results[$position->id]['isTie'])
                        <span class="badge bg-warning text-dark">Tie</span>
                    @elseif ($results[$position->id]['winner'])
                        <span class="badge bg-success">Winner: {{ $results[$position->id]['winner']->candidate_name }}</span>
                    @endif
                </div>
                <table class="table mt-3 mb-0">
                    <thead><tr><th>Candidate</th><th class="text-end">Votes</th><th class="text-end">Percent</th></tr></thead>
                    <tbody>
                    @foreach ($results[$position->id]['candidateCounts'] as $row)
                        <tr>
                            <td>{{ $row['candidate']->candidate_name }}</td>
                            <td class="text-end">{{ $row['votes'] }}</td>
                            <td class="text-end">{{ $row['percentage'] }}%</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection
