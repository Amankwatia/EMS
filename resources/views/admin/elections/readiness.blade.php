@extends('layouts.admin')

@section('title', 'Election Readiness')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h2 class="h4">{{ $election->title }}</h2>
                <p class="text-muted mb-0">Status: {{ ucfirst($election->status) }}</p>
            </div>
            @if ($ready)
                <span class="badge bg-success align-self-start">Ready</span>
            @else
                <span class="badge bg-warning text-dark align-self-start">Needs Attention</span>
            @endif
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @foreach ($checks as $check)
            <div class="list-group-item">
                <div class="d-flex justify-content-between gap-3">
                    <div>
                        <div class="fw-semibold">{{ $check['label'] }}</div>
                        <div class="text-muted small">{{ $check['detail'] }}</div>
                    </div>
                    <span class="badge {{ $check['passed'] ? 'bg-success' : 'bg-danger' }} align-self-center">
                        {{ $check['passed'] ? 'Pass' : 'Fix' }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
