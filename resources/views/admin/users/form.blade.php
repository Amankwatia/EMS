@extends('layouts.admin')

@section('title', $staffUser->exists ? 'Edit Staff User' : 'New Staff User')

@section('content')
<form class="card border-0 shadow-sm" method="POST" action="{{ $staffUser->exists ? route('admin.users.update', $staffUser) : route('admin.users.store') }}">
    @csrf
    @if ($staffUser->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" value="{{ old('name', $staffUser->name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="{{ old('email', $staffUser->email) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ $staffUser->exists ? 'New Password' : 'Password' }}</label>
            <input class="form-control" type="password" name="password" {{ $staffUser->exists ? '' : 'required' }}>
        </div>
        <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <input class="form-control" type="password" name="password_confirmation" {{ $staffUser->exists ? '' : 'required' }}>
        </div>
        <div class="col-12">
            <label class="form-label">Roles</label>
            <div class="row g-2">
                @foreach ($roles as $role)
                    <div class="col-md-4">
                        <label class="form-check border rounded p-2">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(collect(old('roles', $staffUser->roles->pluck('name')->all()))->contains($role->name))>
                            <span class="form-check-label">{{ $role->name }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between">
        @if ($staffUser->exists && ! $staffUser->is(auth()->user()))
            <button class="btn btn-outline-danger" form="delete-user-form" type="submit">Delete</button>
        @else
            <span></span>
        @endif
        <button class="btn btn-primary">Save Staff User</button>
    </div>
</form>

@if ($staffUser->exists && ! $staffUser->is(auth()->user()))
    <form id="delete-user-form" method="POST" action="{{ route('admin.users.destroy', $staffUser) }}">
        @csrf
        @method('DELETE')
    </form>
@endif
@endsection
