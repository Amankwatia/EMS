@extends('layouts.voter')

@section('content')
<div class="card voter-card mx-auto border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-4">Student Voting Login</h1>
        <form method="POST" action="{{ route('voter.login.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Election</label>
                <select class="form-select form-select-lg" name="election_id" required>
                    @foreach ($elections as $election)
                        <option value="{{ $election->id }}" @selected(old('election_id') == $election->id)>{{ $election->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Student ID / Index Number</label>
                <input class="form-control form-control-lg" name="student_id" value="{{ old('student_id') }}" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">PIN / Password</label>
                <input class="form-control form-control-lg" name="pin" type="password" required>
            </div>
            <button class="btn btn-primary btn-lg w-100">Login</button>
        </form>
    </div>
</div>
@endsection
