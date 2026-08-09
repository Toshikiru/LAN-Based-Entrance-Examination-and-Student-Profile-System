@php
    // Reused read-only by the Super Admin — resolve the right shell for whoever is viewing.
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $layout = $isSuperAdmin ? 'layouts.admin' : 'layouts.counselor';
@endphp

<x-dynamic-component :component="$layout" title="Live Monitoring" active="monitoring">

    <x-navigation.header
        title="Live Monitoring"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Examinations', 'href' => route('counselor.exams.index')],
                ['label' => $exam->title, 'href' => $isSuperAdmin ? route('counselor.exams.index') : route('counselor.exams.builder', $exam)],
                ['label' => 'Monitoring'],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-ui.button :href="route('counselor.exams.results', $exam)" variant="outline" icon="grade">Results</x-ui.button>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-lg mb-xl">
        <x-ui.stat-card icon="group" label="Joined" :value="number_format($stats['joined'])" variant="primary" />
        <x-ui.stat-card icon="pending" label="In Progress" :value="number_format($stats['in_progress'])" variant="secondary" />
        <x-ui.stat-card icon="how_to_reg" label="Submitted" :value="number_format($stats['submitted'])" variant="tertiary" />
        <x-ui.stat-card icon="flag" label="Flagged" :value="number_format($stats['flagged'])" variant="neutral" />
    </div>

    <div class="flex flex-wrap items-center justify-between gap-md mb-lg">
        <div class="flex items-center gap-sm text-label-sm text-on-surface-variant">
            <span class="flex items-center gap-xs"><span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span> Live</span>
            <span>· auto-refreshes every 5 seconds</span>
            <span id="monitor-updated" class="text-outline"></span>
        </div>
        <form method="GET" action="{{ route('counselor.exams.monitor', $exam) }}" class="flex items-center gap-sm">
            <x-ui.search-bar name="search" :value="$search ?? ''" placeholder="Search students..." rounded="lg" class="min-w-[240px]" />
            @if ($search)
                <a href="{{ route('counselor.exams.monitor', $exam) }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>
    </div>

    <x-ui.table
        :headers="['Student', 'Status', 'Progress', 'Time Left', 'Submitted', ['label' => 'Actions', 'align' => 'right']]"
        tbody-id="monitor-body"
    >
        @include('counselor.exams.partials.monitor-rows')
    </x-ui.table>

    <script>
        (function () {
            const base = '{{ route('counselor.exams.monitor.data', $exam) }}';
            const search = @json($search ?? '');
            const body = document.getElementById('monitor-body');
            const stamp = document.getElementById('monitor-updated');

            async function poll() {
                try {
                    const url = base + (search ? ('?search=' + encodeURIComponent(search)) : '');
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (res.ok) {
                        body.innerHTML = await res.text();
                        if (stamp) stamp.textContent = 'Updated ' + new Date().toLocaleTimeString();
                    }
                } catch (e) { /* keep last snapshot on failure */ }
            }

            const intervalId = setInterval(poll, 5000);

            // Without this, navigating away via Turbo wouldn't tear down this
            // interval (a hard page reload used to do that for free) — it
            // would keep polling this now-detached table forever in the
            // background. `turbo:before-cache` fires right before Turbo
            // swaps this page out, which is the right moment to stop it.
            document.addEventListener('turbo:before-cache', function stop() {
                clearInterval(intervalId);
                document.removeEventListener('turbo:before-cache', stop);
            }, { once: true });
        })();
    </script>

</x-dynamic-component>
