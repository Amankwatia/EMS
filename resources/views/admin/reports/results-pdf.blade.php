<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        h2 { font-size: 16px; margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 7px; text-align: left; }
        th { background: #f3f4f6; }
        .summary { margin-top: 16px; }
        .muted { color: #6b7280; }
        .signature { margin-top: 48px; display: table; width: 100%; }
        .signature div { display: table-cell; width: 50%; padding-right: 40px; }
        .line { border-top: 1px solid #111827; padding-top: 6px; }
    </style>
</head>
<body>
    @if ($schoolLogoPath)
        <img src="{{ $schoolLogoPath }}" style="height: 64px; margin-bottom: 8px;" alt="">
    @endif
    <h1>{{ $schoolName }}</h1>
    <h2 style="margin-top: 0;">{{ $election->title }}</h2>
    <div class="muted">{{ $election->academic_year }} | Generated {{ now()->format('M d, Y H:i') }}</div>

    <table class="summary">
        <tr><th>Registered Voters</th><td>{{ $registeredVoters }}</td></tr>
        <tr><th>Eligible Voters</th><td>{{ $eligibleVoters }}</td></tr>
        <tr><th>Completed Ballots</th><td>{{ $votersCompleted }}</td></tr>
        <tr><th>Turnout</th><td>{{ $turnout }}%</td></tr>
    </table>

    @foreach ($positions as $position)
        <h2>{{ $position->name }}</h2>
        @if ($results[$position->id]['isTie'])
            <p><strong>Result:</strong> Tie</p>
        @elseif ($results[$position->id]['winner'])
            <p><strong>Winner:</strong> {{ $results[$position->id]['winner']->candidate_name }}</p>
        @endif
        <table>
            <thead><tr><th>Candidate</th><th>Votes</th><th>Percent</th></tr></thead>
            <tbody>
            @foreach ($results[$position->id]['candidateCounts'] as $row)
                <tr>
                    <td>{{ $row['candidate']->candidate_name }}</td>
                    <td>{{ $row['votes'] }}</td>
                    <td>{{ $row['percentage'] }}%</td>
                </tr>
            @endforeach
            @if ($position->allow_abstain)
                <tr>
                    <td>Abstentions</td>
                    <td>{{ $results[$position->id]['abstentions'] }}</td>
                    <td>{{ $results[$position->id]['total'] > 0 ? round(($results[$position->id]['abstentions'] / $results[$position->id]['total']) * 100, 1) : 0 }}%</td>
                </tr>
            @endif
            </tbody>
        </table>
    @endforeach

    <div class="signature">
        <div><p class="line">Electoral Officer</p></div>
        <div><p class="line">Head / Supervisor</p></div>
    </div>
</body>
</html>
