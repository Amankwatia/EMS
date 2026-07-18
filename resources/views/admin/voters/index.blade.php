@extends('layouts.admin')

@section('title', 'Voters')

@section('content')
<div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <h2 class="h4">Voters</h2>
    <div class="d-flex gap-2">
        @can('import voters')
            <a class="btn btn-outline-secondary" href="{{ route('admin.imports.index') }}">Bulk import</a>
        @endcan
        <a class="btn btn-primary" href="{{ route('admin.voters.create') }}">New Voter</a>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2" method="GET">
            <div class="col-md-6"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search by name or student ID"></div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    <option value="voted" @selected(request('status') === 'voted')>Voted</option>
                    <option value="not_voted" @selected(request('status') === 'not_voted')>Not voted</option>
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-outline-primary w-100">Filter</button></div>
        </form>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Student ID</th><th>Name</th><th>Class</th><th>Election</th><th>Eligible</th><th>Voted</th><th></th></tr></thead>
            <tbody>
            @foreach ($voters as $voter)
                <tr>
                    <td>{{ $voter->student_id }}</td>
                    <td>{{ $voter->full_name }}</td>
                    <td>{{ $voter->class_name }}</td>
                    <td>{{ $voter->election->title }}</td>
                    <td>{{ $voter->is_eligible ? 'Yes' : 'No' }}</td>
                    <td>
                        {{ $voter->has_voted ? 'Yes' : 'No' }}
                        @if ($voter->voted_at)
                            <div class="small text-muted">{{ $voter->voted_at->format('M d, H:i') }}</div>
                        @endif
                    </td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.voters.edit', $voter) }}">Edit</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $voters->links() }}
@endsection
