@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Time</th>
                <th>Action</th>
                <th>Description</th>
                <th>User</th>
                <th>Election</th>
                <th>Severity</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td class="text-nowrap">{{ optional($log->created_at)->format('M d, Y H:i') }}</td>
                    <td><code>{{ $log->action }}</code></td>
                    <td>{{ $log->description }}</td>
                    <td>{{ $log->user?->name ?? 'System / Voter' }}</td>
                    <td>{{ $log->election?->title ?? '-' }}</td>
                    <td><span class="badge bg-secondary">{{ $log->severity }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $logs->links() }}
@endsection
