<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'School Voting') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar d-none d-lg-flex">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}">
            <span class="admin-brand-mark">SV</span>
            <span><strong>School Voting</strong><small>Administration</small></span>
        </a>
        <div class="admin-sidebar-scroll">
            @include('admin.partials.navigation')
        </div>
        <div class="admin-sidebar-footer">
            <span class="status-dot"></span>
            <span>System online</span>
        </div>
    </aside>

    <div class="offcanvas offcanvas-start admin-mobile-menu" tabindex="-1" id="adminMenu" aria-labelledby="adminMenuLabel">
        <div class="offcanvas-header">
            <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                <span class="admin-brand-mark">SV</span>
                <span><strong id="adminMenuLabel">School Voting</strong><small>Administration</small></span>
            </a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">@include('admin.partials.navigation')</div>
    </div>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn admin-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMenu" aria-controls="adminMenu" aria-label="Open navigation">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18v2H3V6Zm0 5h18v2H3v-2Zm0 5h18v2H3v-2Z"/></svg>
                </button>
                <div>
                    <h1>@yield('title', 'Dashboard')</h1>
                    <p class="d-none d-sm-block">School election management system</p>
                </div>
            </div>
            <div class="admin-user">
                <span class="admin-avatar">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                <span class="admin-user-copy d-none d-md-block">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->roles->pluck('name')->first() ?? 'Administrator' }}</small>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm">Sign out</button>
                </form>
            </div>
        </header>

        <div class="admin-content">
            @if (session('status'))
                <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm">
                    <strong>Please check the form.</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
