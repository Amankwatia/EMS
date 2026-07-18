@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="page-heading">
    <div>
        <p class="page-eyebrow">Election overview</p>
        <h2>Welcome back, {{ str(auth()->user()->name)->before(' ') }}</h2>
        <p>Monitor participation and manage the current election from one place.</p>
    </div>
    @if ($election)
        @can('manage elections')
            <a class="btn btn-primary" href="{{ route('admin.elections.edit', $election) }}">Manage election</a>
        @endcan
    @else
        @can('manage elections')
            <a class="btn btn-primary" href="{{ route('admin.elections.create') }}">Create election</a>
        @endcan
    @endif
</div>

<div class="metric-grid">
    @foreach ([
        ['label' => 'Registered voters', 'value' => $totalVoters, 'note' => $eligibleVoters.' eligible', 'icon' => 'users'],
        ['label' => 'Students voted', 'value' => $votedVoters, 'note' => $turnout.'% turnout', 'icon' => 'check'],
        ['label' => 'Yet to vote', 'value' => $remainingVoters, 'note' => 'Eligible students', 'icon' => 'clock'],
        ['label' => 'Candidates', 'value' => $totalCandidates, 'note' => $totalPositions.' ballot positions', 'icon' => 'ballot'],
    ] as $metric)
        <article class="metric-card">
            <div class="metric-icon">
                @if ($metric['icon'] === 'users')
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-6 1a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm6 1c-.7 0-1.3.1-1.9.3 2.4 1.2 3.9 2.9 3.9 4.7v3h4v-4c0-2.2-2.7-4-6-4ZM10 14c-4.4 0-8 2.2-8 5v3h16v-3c0-2.8-3.6-5-8-5Z"/></svg>
                @elseif ($metric['icon'] === 'check')
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-2 15-4-4 1.4-1.4 2.6 2.6 6.6-6.6L18 9l-8 8Z"/></svg>
                @elseif ($metric['icon'] === 'clock')
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8Zm1-13h-2v6l5.2 3.1 1-1.7-4.2-2.5V7Z"/></svg>
                @else
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m18 7-3-4H9L6 7H2v15h20V7h-4Zm-8-2h4l1.5 2h-7L10 5Zm10 15H4V9h16v11Zm-5-8h3v2h-3v3h-2v-3h-3v-2h3V9h2v3Z"/></svg>
                @endif
            </div>
            <div class="metric-copy">
                <span>{{ $metric['label'] }}</span>
                <strong>{{ number_format($metric['value']) }}</strong>
                <small>{{ $metric['note'] }}</small>
            </div>
        </article>
    @endforeach
</div>

<div class="dashboard-grid">
    <section class="panel participation-panel">
        <div class="panel-header">
            <div>
                <p class="page-eyebrow">Live participation</p>
                <h3>Voter turnout</h3>
            </div>
            @if ($election)
                <span class="status-pill">{{ ucfirst($election->status) }}</span>
            @endif
        </div>
        @if ($election)
            <div class="turnout-content">
                <div class="turnout-chart" style="--turnout: {{ min(100, $turnout) }}" role="img" aria-label="{{ $turnout }} percent voter turnout">
                    <div><strong>{{ $turnout }}%</strong><span>turnout</span></div>
                </div>
                <div class="turnout-details">
                    <h4>{{ $election->title }}</h4>
                    <p>{{ $votedVoters }} of {{ $eligibleVoters }} eligible students have completed voting.</p>
                    <div class="chart-legend">
                        <div><span class="legend-dot primary"></span><span>Voted</span><strong>{{ $votedVoters }}</strong></div>
                        <div><span class="legend-dot muted"></span><span>Yet to vote</span><strong>{{ $remainingVoters }}</strong></div>
                    </div>
                    <div class="progress turnout-progress" role="progressbar" aria-label="Voter turnout" aria-valuenow="{{ $turnout }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ min(100, $turnout) }}%"></div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span>Updated from recorded ballot submissions</span>
                @can('view turnout')
                    <a href="{{ route('admin.participation.index', ['election_id' => $election->id]) }}">View participation <span aria-hidden="true">→</span></a>
                @endcan
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">+</div>
                <h4>No election created</h4>
                <p>Create an election to begin tracking participation.</p>
            </div>
        @endif
    </section>

    <section class="panel election-panel">
        <div class="panel-header">
            <div>
                <p class="page-eyebrow">Current election</p>
                <h3>Election status</h3>
            </div>
        </div>
        @if ($election)
            <div class="election-summary">
                <div class="election-mark"><span></span></div>
                <h4>{{ $election->title }}</h4>
                <p>{{ $election->academic_year ?: 'Current academic year' }}</p>
            </div>
            <dl class="election-facts">
                <div><dt>Status</dt><dd><span class="status-dot"></span>{{ ucfirst($election->status) }}</dd></div>
                <div><dt>Positions</dt><dd>{{ $totalPositions }}</dd></div>
                <div><dt>Candidates</dt><dd>{{ $totalCandidates }}</dd></div>
                <div><dt>Voting window</dt><dd>{{ $election->end_at ? 'Ends '.$election->end_at->format('M d, H:i') : 'No end time' }}</dd></div>
            </dl>
            <div class="panel-actions">
                @can('manage elections')
                    <a class="btn btn-primary" href="{{ route('admin.elections.edit', $election) }}">Manage election</a>
                @endcan
                @can('view results')
                    @if ($election->resultsViewableByAdmins())
                        <a class="btn btn-outline-secondary" href="{{ route('admin.results.show', $election) }}">View results</a>
                    @else
                        <button class="btn btn-outline-secondary" disabled title="Results become available when the election closes">Results unavailable</button>
                    @endif
                @endcan
            </div>
        @else
            <div class="empty-state"><p>No current election.</p></div>
        @endif
    </section>

    <section class="panel activity-panel">
        <div class="panel-header">
            <div>
                <p class="page-eyebrow">System log</p>
                <h3>Recent activity</h3>
            </div>
            @can('view audit logs')
                <a class="text-link" href="{{ route('admin.audit-logs.index') }}">View all</a>
            @endcan
        </div>
        <div class="activity-list">
            @forelse ($recentActions as $action)
                <div class="activity-item">
                    <span class="activity-marker"></span>
                    <div class="activity-copy">
                        <strong>{{ str($action->action)->replace(['.', '_'], ' ')->title() }}</strong>
                        <p>{{ $action->description }}</p>
                    </div>
                    <time datetime="{{ optional($action->created_at)?->toIso8601String() }}">{{ optional($action->created_at)->diffForHumans(short: true) }}</time>
                </div>
            @empty
                <div class="empty-state py-4"><p>No recent activity yet.</p></div>
            @endforelse
        </div>
    </section>
</div>
@endsection
