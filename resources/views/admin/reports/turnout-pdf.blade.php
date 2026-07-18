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
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    @if ($schoolLogoPath)
        <img src="{{ $schoolLogoPath }}" style="height: 64px; margin-bottom: 8px;" alt="">
    @endif
    <h1>{{ $schoolName }}</h1>
    <h2 style="margin-top: 0;">{{ $election->title }} Turnout Report</h2>
    <div class="muted">Generated {{ now()->format('M d, Y H:i') }}</div>

    <table>
        <tr><th>Registered Voters</th><td>{{ $registeredVoters }}</td></tr>
        <tr><th>Eligible Voters</th><td>{{ $eligibleVoters }}</td></tr>
        <tr><th>Voted</th><td>{{ $voted }}</td></tr>
        <tr><th>Not Voted</th><td>{{ $notVoted }}</td></tr>
        <tr><th>Turnout</th><td>{{ $turnout }}%</td></tr>
    </table>

    <h2>Turnout By Class</h2>
    <table>
        <thead><tr><th>Class</th><th>Registered</th><th>Voted</th><th>Turnout</th></tr></thead>
        <tbody>
        @foreach ($byClass as $row)
            <tr>
                <td>{{ $row->class_name ?: 'Unspecified' }}</td>
                <td>{{ $row->registered }}</td>
                <td>{{ $row->voted }}</td>
                <td>{{ $row->registered > 0 ? round(($row->voted / $row->registered) * 100, 1) : 0 }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
