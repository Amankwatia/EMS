@extends('layouts.admin')

@section('title', 'Staff Users')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4">Staff Users</h2>
    <a class="btn btn-primary" href="{{ route('admin.users.create') }}">New Staff User</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $staffUser)
                <tr>
                    <td>{{ $staffUser->name }}</td>
                    <td>{{ $staffUser->email }}</td>
                    <td>{{ $staffUser->roles->pluck('name')->join(', ') }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $staffUser) }}">Edit</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $users->links() }}
@endsection
