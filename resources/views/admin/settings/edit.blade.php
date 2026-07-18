@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<form class="card border-0 shadow-sm" method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">School Name</label>
            <input class="form-control" name="school_name" value="{{ old('school_name', $settings['school_name'] ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">School Logo</label>
            <input class="form-control" type="file" name="school_logo" accept="image/*">
            @if (! empty($settings['school_logo_path']))
                <div class="form-text">Current: {{ $settings['school_logo_path'] }}</div>
            @endif
        </div>
        <div class="col-12 form-check ms-2">
            <input type="hidden" name="public_results_enabled" value="0">
            <input class="form-check-input" type="checkbox" name="public_results_enabled" value="1" @checked(old('public_results_enabled', ($settings['public_results_enabled'] ?? '0') === '1'))>
            <label class="form-check-label">Enable public result pages after election publication</label>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">Save Settings</button>
    </div>
</form>
@endsection
