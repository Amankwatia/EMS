@extends('layouts.admin')

@section('title', 'Positions')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4">Positions</h2>
    <a class="btn btn-primary" href="{{ route('admin.positions.create') }}">New Position</a>
</div>
<div class="card border-0 shadow-sm">
    <table class="table align-middle mb-0">
        <thead><tr><th>Name</th><th>Election</th><th>Max Choices</th><th>Required</th><th></th></tr></thead>
        <tbody>
        @foreach ($positions as $position)
            <tr>
                <td>{{ $position->name }}</td>
                <td>{{ $position->election->title }}</td>
                <td>{{ $position->max_choices }}</td>
                <td>{{ $position->is_required ? 'Yes' : 'No' }}</td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.positions.edit', $position) }}">Edit</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $positions->links() }}
@endsection
