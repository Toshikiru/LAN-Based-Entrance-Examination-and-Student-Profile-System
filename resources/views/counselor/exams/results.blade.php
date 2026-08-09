@php
    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Results" active="exams">

    <x-navigation.header
        title="Results Management"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => $isSuperAdmin ? route('counselor.exams.index') : route('counselor.exams.builder', $exam)],
                ['label' => 'Results'],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            @unless ($isSuperAdmin)
                <x-ui.button :href="route('counselor.exams.interpretations', $exam)" variant="outline" icon="straighten">Interpretation</x-ui.button>
            @endunless
            <x-ui.button :href="route('counselor.exams.monitor', $exam)" variant="outline" icon="monitor_heart">Monitor</x-ui.button>
            <x-ui.button :href="route('counselor.exams.results.csv', ['exam' => $exam, ...request()->query()])" data-turbo="false" variant="outline" icon="download">CSV</x-ui.button>
            <x-ui.button :href="route('counselor.exams.results.print', ['exam' => $exam, ...request()->query()])" target="_blank" variant="outline" icon="print">Print / PDF</x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($pendingGrading > 0)
        <x-ui.alert variant="warning" icon="hourglass_top" class="mb-lg">
            {{ $pendingGrading }} submission(s) have short-answer items awaiting manual grading.
            @unless ($isSuperAdmin)
                <a href="{{ route('counselor.exams.grading', $exam) }}" class="font-semibold underline ml-xs">Grade now</a>
            @endunless
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-lg">
        <x-ui.stat-card icon="how_to_reg" label="Submissions" :value="number_format($stats['submitted'])" variant="primary" />
        <x-ui.stat-card icon="workspace_premium" label="Passers" :value="number_format($stats['passers'])" variant="secondary" />
        <x-ui.stat-card icon="percent" label="Passing Rate" :value="$stats['passing_rate'] . '%'" variant="tertiary" />
    </div>

    @if ($stats['submitted'] > 0)
        <x-ui.card class="mb-xl">
            <h3 class="font-headline-md text-headline-md text-on-surface mb-md">Score Distribution</h3>
            <div class="h-64"><canvas id="distChart"></canvas></div>
        </x-ui.card>
        <script>
            (function () {
                const el = document.getElementById('distChart');
                if (!el || !window.Chart) return;

                // Read the current theme's design tokens so the chart matches light/dark mode
                // instead of hardcoding a light-mode-only color.
                const styles = getComputedStyle(document.documentElement);
                const primary = styles.getPropertyValue('--color-primary').trim() || '#004ac6';
                const gridColor = styles.getPropertyValue('--color-outline-variant').trim() || '#c3c6d7';
                const tickColor = styles.getPropertyValue('--color-on-surface-variant').trim() || '#434655';

                const chart = new Chart(el, {
                    type: 'bar',
                    data: {
                        labels: @json($distribution['labels']),
                        datasets: [{
                            label: 'Students',
                            data: @json($distribution['counts']),
                            backgroundColor: primary,
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0, color: tickColor }, grid: { color: gridColor } },
                            x: { ticks: { color: tickColor }, grid: { display: false } },
                        },
                    },
                });

                // Tear the chart down before Turbo swaps this page out, so its
                // resize observer doesn't linger on a detached canvas.
                document.addEventListener('turbo:before-cache', function stop() {
                    chart.destroy();
                    document.removeEventListener('turbo:before-cache', stop);
                }, { once: true });
            })();
        </script>
    @endif

    <x-ui.card :padded="false">
        <form method="GET" action="{{ route('counselor.exams.results', $exam) }}" class="p-lg border-b border-outline-variant flex flex-wrap items-center gap-md">
            <x-ui.search-bar name="search" :value="$filters['search'] ?? ''" placeholder="Search by name or ID..." rounded="lg" class="min-w-[240px] flex-1 max-w-sm" />
            <x-ui.select name="result" placeholder="All Results" onchange="this.form.submit()">
                <option value="passers" @selected(($filters['result'] ?? null) === 'passers')>Passers only</option>
                <option value="non-passers" @selected(($filters['result'] ?? null) === 'non-passers')>Non-passers</option>
            </x-ui.select>
            <x-ui.button type="submit" variant="secondary" size="sm" icon="filter_list">Filter</x-ui.button>
            @if (array_filter($filters))
                <a href="{{ route('counselor.exams.results', $exam) }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        <x-ui.table :headers="['Student', 'Score', 'Percentage', 'Result', 'Interpretation', 'Submitted']">
            @forelse ($sessions as $session)
                @php $r = $session->result; @endphp
                <tr class="hover:bg-primary/5 transition-colors">
                    <td class="px-lg py-md">
                        <p class="font-label-md text-on-surface">{{ $session->student?->name }}</p>
                        <p class="text-label-sm text-outline font-mono">{{ $session->student?->school_id }}</p>
                    </td>
                    <td class="px-lg py-md font-label-md text-on-surface">
                        {{ $r ? rtrim(rtrim(number_format($r->total_score, 2), '0'), '.') . ' / ' . rtrim(rtrim(number_format($r->max_score, 2), '0'), '.') : '—' }}
                    </td>
                    <td class="px-lg py-md font-label-md text-on-surface">{{ $r ? rtrim(rtrim(number_format($r->percentage, 2), '0'), '.') . '%' : '—' }}</td>
                    <td class="px-lg py-md">
                        @if ($r)
                            <x-ui.badge :variant="$r->passed ? 'success' : 'error'" dot>{{ $r->passed ? 'Passed' : 'Failed' }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">Pending</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-lg py-md text-label-md text-on-surface-variant">
                        {{ $r ? ($exam->interpretationFor((float) $r->percentage) ?? '—') : '—' }}
                    </td>
                    <td class="px-lg py-md text-label-md text-on-surface-variant">{{ $session->submitted_at?->format('M d, h:i A') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state icon="grade" title="No results yet" description="Results appear here once students submit this exam." />
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$sessions->currentPage()"
                    :total-pages="$sessions->lastPage()"
                    :total-label="'Showing ' . $sessions->firstItem() . '-' . $sessions->lastItem() . ' of ' . $sessions->total() . ' results'"
                />
            </x-slot:footer>
        </x-ui.table>
    </x-ui.card>

</x-dynamic-component>
