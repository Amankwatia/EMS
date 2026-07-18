@extends('layouts.admin')

@section('title', $voter->exists ? 'Edit Voter' : 'New Voter')

@section('content')
@if (session('generated_pin'))
    <div class="alert alert-warning">
        <strong>Generated PIN for {{ session('generated_pin.student_id') }}:</strong>
        <span class="fs-5">{{ session('generated_pin.pin') }}</span>
        <div class="small">Show or print it now. It will not be shown again.</div>
    </div>
@endif

<form class="card border-0 shadow-sm" method="POST" action="{{ $voter->exists ? route('admin.voters.update', $voter) : route('admin.voters.store') }}">
    @csrf
    @if ($voter->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Election</label>
            <select class="form-select" name="election_id" required>
                @foreach ($elections as $election)
                    <option value="{{ $election->id }}" @selected(old('election_id', $voter->election_id) == $election->id)>{{ $election->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Student ID</label>
            <input class="form-control" name="student_id" value="{{ old('student_id', $voter->student_id) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Full Name</label>
            <input class="form-control" name="full_name" value="{{ old('full_name', $voter->full_name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ $voter->exists ? 'New PIN' : 'PIN' }}</label>
            <input class="form-control" name="pin" type="password" {{ $voter->exists ? '' : 'required' }}>
        </div>
        @foreach (['class_name' => 'Class', 'programme' => 'Programme', 'house' => 'House', 'gender' => 'Gender'] as $field => $label)
            <div class="col-md-3">
                <label class="form-label">{{ $label }}</label>
                <input class="form-control" name="{{ $field }}" value="{{ old($field, $voter->{$field}) }}">
            </div>
        @endforeach
        <div class="col-12 form-check ms-2">
            <input type="hidden" name="is_eligible" value="0">
            <input class="form-check-input" type="checkbox" name="is_eligible" value="1" @checked(old('is_eligible', $voter->is_eligible ?? true))>
            <label class="form-check-label">Eligible to vote</label>
        </div>
    </div>
    <div class="card-footer bg-white text-end"><button class="btn btn-primary">Save Voter</button></div>
</form>

@if ($voter->exists && $voter->has_voted)
    @role('Super Admin')
        <form class="card border-0 shadow-sm mt-3" method="POST" action="{{ route('admin.voters.reset-vote', $voter) }}">
            @csrf
            @method('PATCH')
            <div class="card-body">
                <h2 class="h5">Reset Voted Status</h2>
                <p class="text-muted">This only resets the voter status. Anonymous vote records are not edited or deleted.</p>
                <label class="form-label">Reason</label>
                <textarea class="form-control" name="reason" rows="3" required></textarea>
            </div>
            <div class="card-footer bg-white text-end">
                <button class="btn btn-outline-danger">Reset Voted Status</button>
            </div>
        </form>
    @endrole
@endif

@if ($voter->exists)
    <form class="card border-0 shadow-sm mt-3" method="POST" action="{{ route('admin.voters.reset-pin', $voter) }}">
        @csrf
        @method('PATCH')
        <div class="card-body">
            <h2 class="h5">Generate New PIN</h2>
            <p class="text-muted">The new PIN is shown once after reset and is then stored only as a hash.</p>
            <label class="form-label">Reason</label>
            <textarea class="form-control" name="reason" rows="3" required></textarea>
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-outline-secondary">Generate New PIN</button>
        </div>
    </form>
@endif
@endsection
