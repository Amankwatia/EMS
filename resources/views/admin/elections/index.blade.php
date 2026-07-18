@extends('layouts.admin')

@section('title', 'Elections')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4">Elections</h2>
    <a class="btn btn-primary" href="{{ route('admin.elections.create') }}">New Election</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Title</th><th>Academic Year</th><th>Status</th><th>Starts</th><th>Quick Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($elections as $election)
                <tr>
                    <td>{{ $election->title }}</td>
                    <td>{{ $election->academic_year }}</td>
                    <td><span class="badge bg-secondary">{{ $election->status }}</span></td>
                    <td>{{ optional($election->start_at)->format('M d, Y H:i') }}</td>
                    <td>
                        <form class="d-flex gap-2" method="POST" action="{{ route('admin.elections.status', $election) }}">
                            @csrf
                            @method('PATCH')
                            <select class="form-select form-select-sm" name="status">
                                @foreach (['draft', 'scheduled', 'active', 'paused', 'closed', 'published', 'locked'] as $status)
                                    <option value="{{ $status }}" @selected($election->status === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            <input class="form-control form-control-sm" name="lock_reason" placeholder="Lock reason">
                            <button class="btn btn-sm btn-outline-secondary">Apply</button>
                        </form>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.elections.edit', $election) }}">Edit</a>
                        <a class="btn btn-sm btn-outline-info" href="{{ route('admin.elections.readiness', $election) }}">Readiness</a>
                        @if ($election->resultsViewableByAdmins())
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.results.show', $election) }}">Results</a>
                        @else
                            <span class="btn btn-sm btn-outline-secondary disabled" title="Close the election or enable internal preview first">Results Hidden</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $elections->links() }}
@endsection
