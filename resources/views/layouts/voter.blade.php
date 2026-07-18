<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'School Electronic Voting System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="min-vh-100 d-flex align-items-center py-5">
    <div class="container">
        @if (session('status'))
            <div class="alert alert-success voter-card mx-auto">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger voter-card mx-auto">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @yield('content')
    </div>
</main>
</body>
</html>
