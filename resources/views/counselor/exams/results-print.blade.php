<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>Results - {{ $exam->title }}</title>
<style>
    * { font-family: 'Segoe UI', Arial, sans-serif; }
    body { margin: 32px; color: #191c1e; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .meta { color: #434655; font-size: 13px; margin-bottom: 24px; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #c3c6d7; }
    th { text-transform: uppercase; font-size: 11px; letter-spacing: .05em; color: #434655; }
    .pass { color: #006c49; font-weight: 600; }
    .fail { color: #ba1a1a; font-weight: 600; }
    .toolbar { margin-bottom: 16px; }
    button { padding: 8px 16px; border: 1px solid #004ac6; background: #004ac6; color: #fff; border-radius: 8px; cursor: pointer; }
    @media print { .toolbar { display: none; } body { margin: 0; } }
</style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

    <p style="font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #737686; margin: 0 0 4px;">{{ $branding['school_name'] }} &middot; {{ $branding['system_name'] }}</p>
    <h1>{{ $exam->title }} — Examination Results</h1>
    <p class="meta">
        {{ $exam->department?->name ?? 'All courses' }} · {{ $exam->year_level ?? 'All year levels' }} ·
        Generated {{ now()->format('M d, Y h:i A') }} · {{ $sessions->count() }} record(s)
    </p>

    <table>
        <thead>
            <tr>
                <th>Student</th><th>School ID</th><th>Score</th><th>Percentage</th><th>Result</th><th>Interpretation</th><th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sessions as $session)
                @php $r = $session->result; @endphp
                <tr>
                    <td>{{ $session->student?->name }}</td>
                    <td>{{ $session->student?->school_id }}</td>
                    <td>{{ $r ? rtrim(rtrim(number_format($r->total_score, 2), '0'), '.') . ' / ' . rtrim(rtrim(number_format($r->max_score, 2), '0'), '.') : '—' }}</td>
                    <td>{{ $r ? rtrim(rtrim(number_format($r->percentage, 2), '0'), '.') . '%' : '—' }}</td>
                    <td class="{{ $r?->passed ? 'pass' : 'fail' }}">{{ $r ? ($r->passed ? 'Passed' : 'Failed') : 'Pending' }}</td>
                    <td>{{ $r ? ($exam->interpretationFor((float) $r->percentage) ?? '—') : '—' }}</td>
                    <td>{{ $session->submitted_at?->format('M d, Y h:i A') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No results.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
