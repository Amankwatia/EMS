@extends('layouts.admin')

@section('title', $position->exists ? 'Edit Position' : 'New Position')

@section('content')
<form class="card border-0 shadow-sm" method="POST" action="{{ $position->exists ? route('admin.positions.update', $position) : route('admin.positions.store') }}">
    @csrf
    @if ($position->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Election</label>
            <select class="form-select" name="election_id" required>
                @foreach ($elections as $election)
                    <option value="{{ $election->id }}" @selected(old('election_id', $position->election_id) == $election->id)>{{ $election->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" value="{{ old('name', $position->name) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Max Choices</label>
            <input class="form-control" type="number" name="max_choices" value="{{ old('max_choices', $position->max_choices ?? 1) }}" min="1">
        </div>
        <div class="col-md-4">
            <label class="form-label">Display Order</label>
            <input class="form-control" type="number" name="display_order" value="{{ old('display_order', $position->display_order ?? 0) }}" min="0">
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description">{{ old('description', $position->description) }}</textarea>
        </div>
        @foreach (['is_required' => 'Required', 'allow_abstain' => 'Allow abstain', 'is_active' => 'Active'] as $field => $label)
            <div class="col-md-3 form-check ms-2">
                <input type="hidden" name="{{ $field }}" value="0">
                <input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $position->{$field}))>
                <label class="form-check-label">{{ $label }}</label>
            </div>
        @endforeach
    </div>
    <div class="card-footer bg-white text-end"><button class="btn btn-primary">Save Position</button></div>
</form>
@endsection
