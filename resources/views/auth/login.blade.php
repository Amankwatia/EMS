@extends('layouts.voter')

@section('content')
<div class="card voter-card mx-auto border-0 shadow-sm">
    <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-1">Admin Login</h1>
        <p class="text-muted mb-4">Staff and election officers only</p>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input id="email" class="form-control form-control-lg" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input id="password" class="form-control form-control-lg" type="password" name="password" required autocomplete="current-password">
                @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="form-check mb-4">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                <label class="form-check-label" for="remember_me">Remember me</label>
            </div>
            <button class="btn btn-primary btn-lg w-100">Log in</button>
        </form>
        <a class="btn btn-link w-100 mt-3" href="{{ route('voter.login') }}">Student voting login</a>
    </div>
</div>
@endsection
