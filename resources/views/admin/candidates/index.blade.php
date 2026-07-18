@extends('layouts.admin')

@section('title', 'Candidates')

@section('content')
<div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <h2 class="h4">Candidates</h2>
    <div class="d-flex gap-2">
        @can('import candidates')
            <a class="btn btn-outline-secondary" href="{{ route('admin.imports.index') }}">Bulk import</a>
        @endcan
        <a class="btn btn-primary" href="{{ route('admin.candidates.create') }}">New Candidate</a>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Photo</th><th>Name</th><th>Position</th><th>Election</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($candidates as $candidate)
                <tr>
                    <td>
                        @if ($candidate->photo_path)
                            <img class="candidate-photo rounded" src="{{ asset('storage/'.$candidate->photo_path) }}" alt="">
                        @else
                            <div class="candidate-photo rounded"></div>
                        @endif
                    </td>
                    <td>{{ $candidate->candidate_name }}</td>
                    <td>{{ $candidate->position->name }}</td>
                    <td>{{ $candidate->election->title }}</td>
                    <td><span class="badge bg-secondary">{{ $candidate->status }}</span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.candidates.edit', $candidate) }}">Edit</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $candidates->links() }}
@endsection
