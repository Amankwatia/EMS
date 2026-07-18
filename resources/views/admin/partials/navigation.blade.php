<nav class="admin-nav" aria-label="Admin navigation">
    <div class="admin-nav-label">Overview</div>
    <a class="admin-nav-link @if(request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}">
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6v-9h-6v9Zm0-16v5h6V4h-6Z"/></svg>
        <span>Dashboard</span>
    </a>

    <div class="admin-nav-label">Election setup</div>
    @can('manage elections')
        <a class="admin-nav-link @if(request()->routeIs('admin.elections.*')) active @endif" href="{{ route('admin.elections.index') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Zm1 16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9h16v9Zm0-11H4V6a1 1 0 0 1 1-1h1v2h2V5h8v2h2V5h1a1 1 0 0 1 1 1v2Z"/></svg>
            <span>Elections</span>
        </a>
    @endcan
    @can('manage positions')
        <a class="admin-nav-link @if(request()->routeIs('admin.positions.*')) active @endif" href="{{ route('admin.positions.index') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 5h11v2H4V5Zm0 6h11v2H4v-2Zm0 6h11v2H4v-2Zm14-7 4 3-4 3v-6Z"/></svg>
            <span>Positions</span>
        </a>
    @endcan
    @can('manage candidates')
        <a class="admin-nav-link @if(request()->routeIs('admin.candidates.*')) active @endif" href="{{ route('admin.candidates.index') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0-8a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 10c-5 0-9 2.5-9 5.5V22h18v-2.5C21 16.5 17 14 12 14Zm-6.8 6c.5-2.1 3.5-4 6.8-4s6.3 1.9 6.8 4H5.2Z"/></svg>
            <span>Candidates</span>
        </a>
    @endcan
    @can('manage voters')
        <a class="admin-nav-link @if(request()->routeIs('admin.voters.*')) active @endif" href="{{ route('admin.voters.index') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M16 11c1.7 0 3-1.3 3-3s-1.3-3-3-3c-.3 0-.6.1-.9.1A5 5 0 0 0 5 7a5 5 0 0 0 8.9 3.1c.6.6 1.3.9 2.1.9Zm-6-7a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 9c-4.4 0-8 2.2-8 5v3h16v-3c0-2.8-3.6-5-8-5Zm-6 6v-1c0-1.2 2.3-3 6-3s6 1.8 6 3v1H4Zm14.2-6.6C20.5 13.3 22 14.8 22 17v4h-2v-4c0-1.2-.8-2.1-2.5-2.8l.7-1.8Z"/></svg>
            <span>Voters</span>
        </a>
    @endcan

    @canany(['view turnout', 'view results', 'view audit logs'])
        <div class="admin-nav-label">Monitoring</div>
    @endcanany
    @can('view turnout')
        <a class="admin-nav-link @if(request()->routeIs('admin.participation.*')) active @endif" href="{{ route('admin.participation.index') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 19h16v2H2V3h2v16Zm4-2H6v-6h2v6Zm5 0h-2V7h2v10Zm5 0h-2V4h2v13Z"/></svg>
            <span>Voter Participation</span>
        </a>
    @endcan
    @canany(['import voters', 'import candidates'])
        <a class="admin-nav-link @if(request()->routeIs('admin.imports.*')) active @endif" href="{{ route('admin.imports.index') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M11 15.2 7.4 11.6 6 13l6 6 6-6-1.4-1.4-3.6 3.6V3h-2v12.2ZM5 21h14v2H5v-2Z"/></svg>
            <span>Imports</span>
        </a>
    @endcanany
    @can('view audit logs')
        <a class="admin-nav-link @if(request()->routeIs('admin.audit-logs.*')) active @endif" href="{{ route('admin.audit-logs.index') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8Zm1-13h-2v6l5.2 3.1 1-1.7-4.2-2.5V7Z"/></svg>
            <span>Audit Logs</span>
        </a>
    @endcan

    @canany(['manage settings', 'export reports'])
        <div class="admin-nav-label">System</div>
    @endcanany
    @can('manage settings')
        <a class="admin-nav-link @if(request()->routeIs('admin.settings.*')) active @endif" href="{{ route('admin.settings.edit') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m19.4 13 .1-1-.1-1 2.1-1.6-2-3.4-2.5 1a8.6 8.6 0 0 0-1.7-1L15 3.3h-4L10.6 6a7.3 7.3 0 0 0-1.7 1L6.4 6l-2 3.4L6.5 11l-.1 1 .1 1-2.1 1.6 2 3.4 2.5-1a8.6 8.6 0 0 0 1.7 1l.4 2.7h4l.4-2.7a7.3 7.3 0 0 0 1.7-1l2.5 1 2-3.4-2.2-1.6ZM13 18.7h-.3l-.3-2.2a5.4 5.4 0 0 1-2.8-1.7l-2.1.8-.2-.3L9 14a6 6 0 0 1 0-3.2L7.3 9.5l.2-.3 2.1.8a5.4 5.4 0 0 1 2.8-1.7l.3-2.2h.3l.3 2.2a5.4 5.4 0 0 1 2.8 1.7l2.1-.8.2.3-1.7 1.3a6 6 0 0 1 0 3.2l1.7 1.3-.2.3-2.1-.8a5.4 5.4 0 0 1-2.8 1.7l-.3 2.2ZM12.8 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm0 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/></svg>
            <span>Settings</span>
        </a>
    @endcan
    @role('Super Admin')
        <a class="admin-nav-link @if(request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0-8a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 10c-5 0-9 2.5-9 5.5V22h18v-2.5C21 16.5 17 14 12 14Zm-6.8 6c.5-2.1 3.5-4 6.8-4s6.3 1.9 6.8 4H5.2Z"/></svg>
            <span>Staff Users</span>
        </a>
    @endrole
    @canany(['manage settings', 'export reports'])
        <a class="admin-nav-link @if(request()->routeIs('admin.backup-guide')) active @endif" href="{{ route('admin.backup-guide') }}">
            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M19 5h-2.3A8 8 0 1 0 20 12h-2a6 6 0 1 1-2.7-5H12v5h5V7.4A5.9 5.9 0 0 1 18 12h2a7.9 7.9 0 0 0-1-3.9V5Z"/></svg>
            <span>Backup Guide</span>
        </a>
    @endcanany
</nav>
