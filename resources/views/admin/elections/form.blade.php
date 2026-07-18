@extends('layouts.admin')

@section('title', $election->exists ? 'Edit Election' : 'New Election')

@section('content')
<form class="card border-0 shadow-sm" method="POST" action="{{ $election->exists ? route('admin.elections.update', $election) : route('admin.elections.store') }}">
    @csrf
    @if ($election->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-8">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" value="{{ old('title', $election->title) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Academic Year</label>
            <input class="form-control" name="academic_year" value="{{ old('academic_year', $election->academic_year) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Start At</label>
            <input class="form-control" type="datetime-local" name="start_at" value="{{ old('start_at', optional($election->start_at)->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">End At</label>
            <input class="form-control" type="datetime-local" name="end_at" value="{{ old('end_at', optional($election->end_at)->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                @foreach (['draft', 'scheduled', 'active', 'paused', 'closed', 'published', 'locked'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $election->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3">{{ old('description', $election->description) }}</textarea>
        </div>
        <div class="col-md-6 form-check ms-2">
            <input type="hidden" name="results_visible_to_public" value="0">
            <input class="form-check-input" type="checkbox" name="results_visible_to_public" value="1" @checked(old('results_visible_to_public', $election->results_visible_to_public))>
            <label class="form-check-label">Public results visible after publication</label>
        </div>
        <div class="col-md-6 form-check">
            <input type="hidden" name="allow_internal_live_preview" value="0">
            <input class="form-check-input" type="checkbox" name="allow_internal_live_preview" value="1" @checked(old('allow_internal_live_preview', $election->allow_internal_live_preview))>
            <label class="form-check-label">Allow internal result preview</label>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">Save Election</button>
    </div>
</form>
@endsection
