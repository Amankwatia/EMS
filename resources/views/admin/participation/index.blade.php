@extends('layouts.admin')

@section('title', 'Voter Participation')

@section('content')
<div class="page-heading">
    <div>
        <p class="page-eyebrow">Turnout monitoring</p>
        <h2>Voter participation</h2>
        <p>See who has completed voting without revealing any student's ballot choices.</p>
    </div>
    @if ($election)
        @can('export reports')
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="{{ route('admin.exports.voted-status.csv', ['election' => $election, 'status' => 'voted']) }}">Download voted</a>
                <a class="btn btn-outline-secondary" href="{{ route('admin.exports.voted-status.csv', ['election' => $election, 'status' => 'not_voted']) }}">Download not voted</a>
            </div>
        @endcan
    @endif
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET">
            <div class="col-md-5">
                <label class="form-label" for="election_id">Election</label>
                <select class="form-select" id="election_id" name="election_id">
                    @foreach ($elections as $item)
                        <option value="{{ $item->id }}" @selected($election?->id === $item->id)>{{ $item->title }} ({{ ucfirst($item->status) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="q">Find a student</label>
                <input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="Name, student ID, class, or programme">
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">View</button></div>
        </form>
    </div>
</div>

@if (! $election)
    <div class="alert alert-info">Create an election and add voters to see participation details.</div>
@else
    <div class="row g-3 mb-4 participation-summary">
        @foreach ([
            'Registered' => $registered,
            'Eligible' => $eligible,
            'Voted' => $voted,
            'Not Voted' => $notVoted,
            'Turnout' => $turnout.'%',
        ] as $label => $value)
            <div class="col-6 col-md">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="h3 mb-0">{{ $value }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    @if (request()->filled('q'))
        <p class="text-muted">Showing matches for <strong>{{ request('q') }}</strong>.</p>
    @endif

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="h5 mb-0">Students Who Voted</h3>
            <span class="badge text-bg-light">{{ $votedStudents->total() }} shown</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Student ID</th><th>Name</th><th>Class</th><th>Programme</th><th>House</th><th>Gender</th><th>Voted At</th></tr></thead>
                <tbody>
                @forelse ($votedStudents as $student)
                    <tr>
                        <td>{{ $student->student_id }}</td>
                        <td>{{ $student->full_name }}</td>
                        <td>{{ $student->class_name ?: '—' }}</td>
                        <td>{{ $student->programme ?: '—' }}</td>
                        <td>{{ $student->house ?: '—' }}</td>
                        <td>{{ $student->gender ?: '—' }}</td>
                        <td>{{ $student->voted_at?->format('M d, Y H:i') ?: 'Recorded' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No students found in this list.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($votedStudents->hasPages())
            <div class="card-footer bg-white">{{ $votedStudents->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="h5 mb-0">Students Who Have Not Voted</h3>
            <span class="badge text-bg-light">{{ $notVotedStudents->total() }} shown</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Student ID</th><th>Name</th><th>Class</th><th>Programme</th><th>House</th><th>Gender</th><th>Eligible</th></tr></thead>
                <tbody>
                @forelse ($notVotedStudents as $student)
                    <tr>
                        <td>{{ $student->student_id }}</td>
                        <td>{{ $student->full_name }}</td>
                        <td>{{ $student->class_name ?: '—' }}</td>
                        <td>{{ $student->programme ?: '—' }}</td>
                        <td>{{ $student->house ?: '—' }}</td>
                        <td>{{ $student->gender ?: '—' }}</td>
                        <td><span class="badge {{ $student->is_eligible ? 'text-bg-primary' : 'text-bg-light' }}">{{ $student->is_eligible ? 'Yes' : 'No' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No students found in this list.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($notVotedStudents->hasPages())
            <div class="card-footer bg-white">{{ $notVotedStudents->links() }}</div>
        @endif
    </div>
@endif
@endsection
