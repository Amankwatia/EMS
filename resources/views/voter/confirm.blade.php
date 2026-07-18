@extends('layouts.voter')

@section('content')
<div class="card voter-card mx-auto border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-4">Confirm Your Details</h1>
        <dl class="row">
            <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $voter->full_name }}</dd>
            <dt class="col-sm-4">Student ID</dt><dd class="col-sm-8">{{ $voter->student_id }}</dd>
            <dt class="col-sm-4">Class</dt><dd class="col-sm-8">{{ $voter->class_name }}</dd>
            <dt class="col-sm-4">Programme</dt><dd class="col-sm-8">{{ $voter->programme }}</dd>
        </dl>
        @if (! $voter->is_eligible)
            <div class="alert alert-warning">You are not currently eligible to vote.</div>
        @elseif ($voter->has_voted)
            <div class="alert alert-info">Voting has already been completed for this student.</div>
        @else
            <a class="btn btn-primary btn-lg w-100" href="{{ route('voter.ballot') }}">Proceed to Vote</a>
        @endif
        <form class="mt-3" method="POST" action="{{ route('voter.logout') }}">
            @csrf
            <button class="btn btn-outline-secondary w-100">Logout</button>
        </form>
    </div>
</div>
@endsection
