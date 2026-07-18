@extends('layouts.admin')

@section('title', $candidate->exists ? 'Edit Candidate' : 'New Candidate')

@section('content')
<form class="card border-0 shadow-sm" method="POST" enctype="multipart/form-data" action="{{ $candidate->exists ? route('admin.candidates.update', $candidate) : route('admin.candidates.store') }}">
    @csrf
    @if ($candidate->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Election</label>
            <select class="form-select" name="election_id" required>
                @foreach ($elections as $election)
                    <option value="{{ $election->id }}" @selected(old('election_id', $candidate->election_id) == $election->id)>{{ $election->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Position</label>
            <select class="form-select" name="position_id" required>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" @selected(old('position_id', $candidate->position_id) == $position->id)>{{ $position->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Candidate Name</label>
            <input class="form-control" name="candidate_name" value="{{ old('candidate_name', $candidate->candidate_name) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Student ID</label>
            <input class="form-control" name="student_id" value="{{ old('student_id', $candidate->student_id) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Class</label>
            <input class="form-control" name="class_name" value="{{ old('class_name', $candidate->class_name) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Programme</label>
            <input class="form-control" name="programme" value="{{ old('programme', $candidate->programme) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">House</label>
            <input class="form-control" name="house" value="{{ old('house', $candidate->house) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Gender</label>
            <input class="form-control" name="gender" value="{{ old('gender', $candidate->gender) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Ballot Number</label>
            <input class="form-control" name="ballot_number" value="{{ old('ballot_number', $candidate->ballot_number) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Display Order</label>
            <input class="form-control" type="number" name="display_order" min="0" value="{{ old('display_order', $candidate->display_order ?? 0) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                @foreach (['active', 'inactive', 'disqualified'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $candidate->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Photo</label>
            <input class="form-control" type="file" name="photo" accept="image/*">
        </div>
        <div class="col-12">
            <label class="form-label">Manifesto</label>
            <textarea class="form-control" name="manifesto" rows="3">{{ old('manifesto', $candidate->manifesto) }}</textarea>
        </div>
    </div>
    <div class="card-footer bg-white text-end"><button class="btn btn-primary">Save Candidate</button></div>
</form>
@endsection
