<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>Exam Statistics (Deep Dive)</title>
<style>
    * { font-family: 'Segoe UI', Arial, sans-serif; }
    body { margin: 32px; color: #191c1e; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .meta { color: #434655; font-size: 13px; margin-bottom: 24px; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #c3c6d7; }
    th { text-transform: uppercase; font-size: 11px; letter-spacing: .05em; color: #434655; }
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
    <h1>Exam Statistics (Deep Dive)</h1>
    <p class="meta">{{ $rangeLabel }} &middot; Generated {{ now()->format('M d, Y h:i A') }} &middot; {{ $rows->count() }} examination(s)</p>

    <table>
        <thead>
            <tr><th>Examination</th><th>Completed (n)</th><th>Mean %</th><th>Median %</th><th>Std. Dev.</th><th>Pass Rate</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['title'] }}</td>
                    <td>{{ $row['n'] }}</td>
                    <td>{{ $row['mean'] }}%</td>
                    <td>{{ $row['median'] }}%</td>
                    <td>{{ $row['stdev'] }}</td>
                    <td>{{ $row['pass_rate'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="6">No completed exams in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
