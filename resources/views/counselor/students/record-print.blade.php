<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>Cumulative Record - {{ $student->name }}</title>
<style>
    * { font-family: 'Segoe UI', Arial, sans-serif; }
    body { margin: 32px; color: #191c1e; }
    h1 { font-size: 22px; margin: 0 0 4px; }
    h2 { font-size: 15px; margin: 24px 0 8px; padding-left: 10px; border-left: 4px solid #004ac6; }
    .meta { color: #434655; font-size: 13px; margin-bottom: 24px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #004ac6; padding-bottom: 16px; margin-bottom: 24px; }
    .header-right { text-align: right; font-size: 12px; color: #434655; }
    .student-box { background: #f2f4f6; border: 1px solid #c3c6d7; border-radius: 8px; padding: 16px; margin-bottom: 8px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; }
    .student-box p { margin: 0; }
    .student-box .label { font-size: 11px; text-transform: uppercase; color: #434655; letter-spacing: .05em; }
    .student-box .value { font-size: 15px; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 8px; }
    th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #c3c6d7; }
    th { text-transform: uppercase; font-size: 11px; letter-spacing: .05em; color: #434655; background: #eceef0; }
    .pass { color: #006c49; font-weight: 600; }
    .fail { color: #ba1a1a; font-weight: 600; }
    .pending { color: #737686; font-weight: 600; }
    .stat-row { display: flex; gap: 16px; margin-bottom: 16px; }
    .stat-card { flex: 1; border: 1px solid #c3c6d7; border-radius: 8px; padding: 12px 16px; }
    .stat-card .label { font-size: 11px; text-transform: uppercase; color: #434655; }
    .stat-card .value { font-size: 22px; font-weight: 700; }
    .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 48px; text-align: center; }
    .signatures .line { border-top: 1px solid #191c1e; margin-top: 48px; padding-top: 6px; font-weight: 600; font-size: 13px; }
    .signatures .role { font-size: 11px; color: #434655; }
    .toolbar { margin-bottom: 16px; }
    button { padding: 8px 16px; border: 1px solid #004ac6; background: #004ac6; color: #fff; border-radius: 8px; cursor: pointer; }
    footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #c3c6d7; display: flex; justify-content: space-between; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #737686; }
    @media print { .toolbar { display: none; } body { margin: 0; } }
</style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="header">
        <div>
            <h1>Cumulative Student Record</h1>
            <p class="meta">Guidance &amp; Counseling Department</p>
        </div>
        <div class="header-right">
            <p>{{ $branding['school_name'] }}</p>
            <p>Date Generated</p>
            <p><strong>{{ now()->format('F d, Y') }}</strong></p>
            <p style="margin-top: 8px;">Document Ref: GP-{{ $student->school_id }}</p>
        </div>
    </div>

    <div class="student-box">
        <div>
            <p class="label">Full Name</p>
            <p class="value">{{ $student->name }}</p>
        </div>
        <div>
            <p class="label">Student ID</p>
            <p class="value">{{ $student->school_id }}</p>
        </div>
        <div>
            <p class="label">Department</p>
            <p class="value">{{ $student->department?->name ?? '—' }}</p>
        </div>
        <div>
            <p class="label">Year Level &amp; Section</p>
            <p class="value">{{ $student->studentProfile?->year_level ?? '—' }}{{ $student->studentProfile?->section ? ' · ' . $student->studentProfile->section : '' }}</p>
        </div>
    </div>

    <h2>Examination Summary</h2>
    <div class="stat-row">
        <div class="stat-card">
            <p class="label">Total Exams</p>
            <p class="value">{{ $assessment['total_exams'] }}</p>
        </div>
        <div class="stat-card">
            <p class="label">Average Score</p>
            <p class="value">{{ $assessment['total_exams'] > 0 ? $assessment['average_percentage'] . '%' : '—' }}</p>
        </div>
        <div class="stat-card">
            <p class="label">Highest Score</p>
            <p class="value">{{ $assessment['highest'] ? round($assessment['highest']['percentage'], 1) . '%' : '—' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr><th>Examination</th><th>Date</th><th>Score</th><th>Status</th><th>Interpretation</th></tr>
        </thead>
        <tbody>
            @forelse ($examHistory as $row)
                <tr>
                    <td>{{ $row['title'] }}</td>
                    <td>{{ $row['date']?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $row['score'] !== null ? rtrim(rtrim(number_format($row['score'], 2), '0'), '.') . ' / ' . rtrim(rtrim(number_format($row['max_score'], 2), '0'), '.') : '—' }}</td>
                    <td class="{{ $row['passed'] === true ? 'pass' : ($row['passed'] === false ? 'fail' : 'pending') }}">
                        {{ $row['passed'] === true ? 'Passed' : ($row['passed'] === false ? 'Failed' : ucfirst(str_replace('_', ' ', $row['status']->value))) }}
                    </td>
                    <td>{{ $row['interpretation'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No examination records on file.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Assessment Breakdown (by Section)</h2>
    @forelse ($assessment['breakdowns'] as $breakdown)
        <p style="font-size: 13px; font-weight: 600; margin: 12px 0 4px;">{{ $breakdown['exam']?->title }} — {{ round($breakdown['percentage'], 1) }}%</p>
        <table>
            <thead>
                <tr><th>Section</th><th>Score</th><th>Percentage</th></tr>
            </thead>
            <tbody>
                @foreach ($breakdown['sections'] as $section)
                    <tr>
                        <td>{{ $section['label'] }}</td>
                        <td>{{ rtrim(rtrim(number_format($section['score'], 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($section['max'], 2), '0'), '.') }}</td>
                        <td>{{ $section['percentage'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="meta">No completed, scored examinations on file yet.</p>
    @endforelse

    <div class="signatures">
        <div>
            <div class="line">Guidance Counselor</div>
            <p class="role">Signature over Printed Name</p>
        </div>
        <div>
            <div class="line">{{ strtoupper($student->name) }}</div>
            <p class="role">Student — Signature over Printed Name</p>
        </div>
    </div>

    <footer>
        <p>{{ $branding['system_name'] }} &copy; {{ now()->year }}</p>
        <p>Confidential Academic Document</p>
    </footer>
</body>
</html>
