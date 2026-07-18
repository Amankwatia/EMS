@extends('layouts.admin')

@section('title', 'Candidates')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4">Candidates</h2>
    <a class="btn btn-primary" href="{{ route('admin.candidates.create') }}">New Candidate</a>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="POST" enctype="multipart/form-data" action="{{ route('admin.candidates.import') }}">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Election</label>
                <select class="form-select" name="election_id" required>
                    @foreach ($elections as $election)
                        <option value="{{ $election->id }}">{{ $election->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">CSV file</label>
                <input class="form-control" type="file" name="file" accept=".csv,text/csv" required>
            </div>
            <div class="col-md-3"><button class="btn btn-secondary w-100">Import Candidates</button></div>
        </form>
        <div class="form-text mt-2">Columns: position, candidate_name, student_id, class_name, programme, house, gender, manifesto</div>
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
