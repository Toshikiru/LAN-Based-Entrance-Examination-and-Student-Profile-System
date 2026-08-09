@php
    // CSS custom properties (not hex) so this conic-gradient stays correct in dark mode.
    $bucketColors = ['easy' => 'var(--color-secondary)', 'optimal' => 'var(--color-primary)', 'hard' => 'var(--color-tertiary-fixed-dim)', 'poor' => 'var(--color-error)'];
    $bucketLabels = ['easy' => 'Easy', 'optimal' => 'Optimal', 'hard' => 'Hard', 'poor' => 'Poor'];

    $cumulative = 0;
    $stops = [];
    foreach ($difficulty['percentages'] as $key => $pct) {
        $startDeg = round($cumulative * 3.6, 2);
        $cumulative += $pct;
        $endDeg = round($cumulative * 3.6, 2);
        if ($pct > 0) {
            $stops[] = "{$bucketColors[$key]} {$startDeg}deg {$endDeg}deg";
        }
    }
    $conicGradient = count($stops) > 0 ? 'conic-gradient(' . implode(', ', $stops) . ')' : null;

    $maxPassRate = max(1, ...$trend['pass_rates']);

    // Full Access for both roles — resolve the right shell for whoever is viewing.
    $layout = auth()->user()->isSuperAdmin() ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Reports & Analytics" active="reports">

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-md mb-lg">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Analytics Overview</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Real-time performance metrics across all examinations.</p>
        </div>
        <div class="flex items-center gap-sm bg-surface-container-low p-1 rounded-xl border border-outline-variant">
            @foreach ($ranges as $key => $label)
                <a
                    href="{{ route('counselor.reports.index', ['range' => $key]) }}"
                    @class([
                        'px-md py-2 rounded-lg font-label-md text-label-md transition-colors',
                        'bg-surface text-primary shadow-sm border border-outline-variant' => $range === $key,
                        'text-on-surface-variant hover:bg-surface-container' => $range !== $key,
                    ])
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-lg mb-lg">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg flex flex-col gap-xs hover:shadow-md transition-shadow">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Average Pass Rate</span>
            <span class="font-display-lg-mobile text-[36px] text-secondary font-bold">{{ $kpis['pass_rate'] }}%</span>
            <div class="w-full bg-secondary/10 h-1 rounded-full mt-xs overflow-hidden">
                <div class="bg-secondary h-full" style="width: {{ $kpis['pass_rate'] }}%"></div>
            </div>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg flex flex-col gap-xs hover:shadow-md transition-shadow">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Total Exams Taken</span>
            <span class="font-display-lg-mobile text-[36px] text-primary font-bold">{{ number_format($kpis['total_exams_taken']) }}</span>
            <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $ranges[$range] }}</p>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg flex flex-col gap-xs hover:shadow-md transition-shadow">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Avg. Difficulty (p-value)</span>
            <div class="flex items-baseline gap-sm">
                <span class="font-display-lg-mobile text-[36px] text-on-surface font-bold">{{ $kpis['avg_difficulty'] ?? '—' }}</span>
                @if ($kpis['avg_difficulty'] !== null)
                    @php
                        $avgBucketLabel = match (true) {
                            $kpis['avg_difficulty'] >= 0.8 => 'Easy',
                            $kpis['avg_difficulty'] >= 0.4 => 'Optimal',
                            $kpis['avg_difficulty'] >= 0.2 => 'Hard',
                            default => 'Poor',
                        };
                    @endphp
                    <span class="px-2 py-0.5 bg-surface-container-high rounded text-[10px] font-bold uppercase">{{ $avgBucketLabel }}</span>
                @endif
            </div>
            <p class="font-label-sm text-label-sm text-on-surface-variant">Based on {{ $difficulty['total_questions'] }} question(s) analyzed</p>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg flex flex-col gap-xs hover:shadow-md transition-shadow">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-widest">Proctor Flags</span>
            <span class="font-display-lg-mobile text-[36px] text-error font-bold">{{ $kpis['flagged_rate'] }}%</span>
            <p class="font-label-sm text-label-sm text-on-surface-variant">Sessions flagged or terminated</p>
        </div>
    </div>

    {{-- Visualization Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg mb-lg">
        {{-- Performance Trend --}}
        <div class="lg:col-span-2">
            <x-ui.card title="Student Performance Trends" subtitle="Pass rate per {{ $range === '30d' ? 'week' : 'month' }}, this period.">
                <div class="h-64 flex items-end gap-sm">
                    @forelse ($trend['labels'] as $i => $label)
                        <div class="flex-1 flex flex-col items-center gap-xs h-full justify-end group">
                            <span class="text-[11px] font-bold text-on-surface-variant opacity-0 group-hover:opacity-100 transition-opacity">{{ $trend['pass_rates'][$i] }}% ({{ $trend['volumes'][$i] }})</span>
                            <div class="w-full bg-primary/70 rounded-t-lg transition-all" style="height: {{ max(2, round(($trend['pass_rates'][$i] / $maxPassRate) * 100)) }}%"></div>
                            <span class="text-[10px] font-bold text-outline uppercase">{{ $label }}</span>
                        </div>
                    @empty
                        <div class="w-full h-full flex items-center justify-center">
                            <x-ui.empty-state icon="query_stats" title="No completed exams in this period" description="Pass rate trends will appear once exams are submitted." />
                        </div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        {{-- Item Analysis --}}
        <x-ui.card title="Item Analysis">
            <div class="flex flex-col items-center">
                @if ($conicGradient)
                    <div class="relative w-40 h-40 mb-lg">
                        <div class="w-full h-full rounded-full" style="background: {{ $conicGradient }};"></div>
                        <div class="absolute inset-2 bg-surface-container-lowest rounded-full flex flex-col items-center justify-center">
                            <span class="font-headline-md text-headline-md font-bold">{{ $difficulty['total_questions'] }}</span>
                            <span class="text-[10px] text-on-surface-variant text-center px-sm">Questions Analyzed</span>
                        </div>
                    </div>
                    <div class="w-full grid grid-cols-2 gap-sm">
                        @foreach ($bucketLabels as $key => $label)
                            <div class="flex items-center gap-sm">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $bucketColors[$key] }}"></span>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $label }} ({{ $difficulty['percentages'][$key] }}%)</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state icon="troubleshoot" title="No graded answers yet" description="Difficulty analysis appears once students answer questions in this period." />
                @endif
            </div>
        </x-ui.card>
    </div>

    {{-- Downloadable Reports --}}
    <x-ui.card title="Downloadable Reports" :padded="false" class="mb-lg">
        <div class="divide-y divide-outline-variant">
            <div class="p-lg hover:bg-surface-container-low transition-colors flex flex-col md:flex-row md:items-center gap-lg">
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center text-primary shrink-0">
                    <span class="material-symbols-outlined text-[28px]">description</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-body-lg text-body-lg font-semibold text-on-surface">Student Performance Summary</h4>
                    <p class="font-label-sm text-label-sm text-on-surface-variant">Per-student exams taken, average score, and pass rate for {{ $ranges[$range] }}.</p>
                </div>
                <div class="flex items-center gap-sm shrink-0">
                    <a href="{{ route('counselor.reports.student-summary.print', ['range' => $range]) }}" target="_blank" class="flex items-center gap-sm px-md py-2 border border-primary text-primary rounded-lg font-label-md hover:bg-primary/5 transition-all">
                        <span class="material-symbols-outlined text-[18px]">print</span> Print / PDF
                    </a>
                    <a href="{{ route('counselor.reports.student-summary.csv', ['range' => $range]) }}" data-turbo="false" class="flex items-center gap-sm px-md py-2 border border-outline-variant text-on-surface-variant rounded-lg font-label-md hover:bg-surface-container transition-all">
                        <span class="material-symbols-outlined text-[18px]">table_chart</span> CSV
                    </a>
                </div>
            </div>

            <div class="p-lg hover:bg-surface-container-low transition-colors flex flex-col md:flex-row md:items-center gap-lg">
                <div class="w-12 h-12 rounded-lg bg-secondary/10 flex items-center justify-center text-secondary shrink-0">
                    <span class="material-symbols-outlined text-[28px]">analytics</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-body-lg text-body-lg font-semibold text-on-surface">Exam Statistics (Deep Dive)</h4>
                    <p class="font-label-sm text-label-sm text-on-surface-variant">Mean, median, standard deviation, and pass rate per examination.</p>
                </div>
                <div class="flex items-center gap-sm shrink-0">
                    <a href="{{ route('counselor.reports.exam-statistics.print', ['range' => $range]) }}" target="_blank" class="flex items-center gap-sm px-md py-2 border border-primary text-primary rounded-lg font-label-md hover:bg-primary/5 transition-all">
                        <span class="material-symbols-outlined text-[18px]">print</span> Print / PDF
                    </a>
                    <a href="{{ route('counselor.reports.exam-statistics.csv', ['range' => $range]) }}" data-turbo="false" class="flex items-center gap-sm px-md py-2 border border-outline-variant text-on-surface-variant rounded-lg font-label-md hover:bg-surface-container transition-all">
                        <span class="material-symbols-outlined text-[18px]">table_chart</span> CSV
                    </a>
                </div>
            </div>

            <div class="p-lg hover:bg-surface-container-low transition-colors flex flex-col md:flex-row md:items-center gap-lg">
                <div class="w-12 h-12 rounded-lg bg-tertiary/10 flex items-center justify-center text-tertiary shrink-0">
                    <span class="material-symbols-outlined text-[28px]">troubleshoot</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-body-lg text-body-lg font-semibold text-on-surface">Item Analysis Detail</h4>
                    <p class="font-label-sm text-label-sm text-on-surface-variant">Question-by-question p-value and empirical difficulty. CSV only.</p>
                </div>
                <div class="flex items-center gap-sm shrink-0">
                    <span class="flex items-center gap-sm px-md py-2 border border-outline-variant text-outline rounded-lg font-label-md opacity-50 cursor-not-allowed">
                        <span class="material-symbols-outlined text-[18px]">print</span> Print / PDF
                    </span>
                    <a href="{{ route('counselor.reports.item-analysis.csv', ['range' => $range]) }}" data-turbo="false" class="flex items-center gap-sm px-md py-2 border border-outline-variant text-on-surface-variant rounded-lg font-label-md hover:bg-surface-container transition-all">
                        <span class="material-symbols-outlined text-[18px]">table_chart</span> CSV
                    </a>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Automated Report Schedule --}}
    <x-ui.card title="Automated Report Schedule" subtitle="Configures who should receive which report and how often. Scheduled email dispatch is not wired up yet — this only stores the configuration.">
        <x-slot:actions>
            <x-ui.button type="button" icon="add" size="sm" @click="$dispatch('open-modal-add-schedule')">Add Schedule</x-ui.button>
        </x-slot:actions>

        <x-ui.table :headers="['Report', 'Recipient', 'Frequency', 'Last Run', 'Status', '']">
            @forelse ($schedules as $schedule)
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-4 py-4 text-on-surface">{{ $schedule->report_type->label() }}</td>
                    <td class="px-4 py-4">
                        <p class="text-on-surface">{{ $schedule->recipient_name }}</p>
                        <p class="text-label-sm text-outline">{{ $schedule->recipient_email }}</p>
                    </td>
                    <td class="px-4 py-4 text-on-surface-variant">{{ \App\Models\ReportSchedule::FREQUENCIES[$schedule->frequency] ?? ucfirst($schedule->frequency) }}</td>
                    <td class="px-4 py-4 text-on-surface-variant">{{ $schedule->last_run_at?->format('M d, Y') ?? 'Never' }}</td>
                    <td class="px-4 py-4">
                        <x-ui.badge :variant="$schedule->is_active ? 'success' : 'neutral'" dot>{{ $schedule->is_active ? 'Active' : 'Paused' }}</x-ui.badge>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <form method="POST" action="{{ route('counselor.reports.schedules.toggle', $schedule) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-primary font-label-md text-label-md hover:underline">{{ $schedule->is_active ? 'Pause' : 'Enable' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-4">
                        <x-ui.empty-state icon="schedule_send" title="No report schedules yet" description="Add a schedule to configure recurring report delivery." />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>

    <x-ui.modal name="add-schedule" title="Add Report Schedule" max-width="md">
        <form method="POST" action="{{ route('counselor.reports.schedules.store') }}" id="form-add-schedule" class="space-y-lg">
            @csrf
            <x-ui.select name="report_type" label="Report">
                @foreach (\App\Enums\ReportType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('report_type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input name="recipient_name" label="Recipient Name" :value="old('recipient_name')" />
            <x-ui.input type="email" name="recipient_email" label="Recipient Email" :value="old('recipient_email')" />
            <x-ui.select name="frequency" label="Frequency">
                @foreach (\App\Models\ReportSchedule::FREQUENCIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('frequency') === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" type="button" @click="open = false">Cancel</x-ui.button>
            <x-ui.button type="submit" form="form-add-schedule" icon="save">Save Schedule</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

</x-dynamic-component>
