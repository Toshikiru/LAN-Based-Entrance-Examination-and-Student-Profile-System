<x-layouts.admin title="Audit Logs" active="audit-logs">

    <x-navigation.header
        title="Audit Logs"
        subtitle="A read-only record of actions taken across the system."
    />

    <x-ui.card :padded="false">
        {{-- Search --}}
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="p-lg border-b border-outline-variant flex flex-wrap items-center gap-md">
            <x-ui.search-bar
                name="search"
                :value="$filters['search'] ?? ''"
                placeholder="Search by action, description, or user..."
                rounded="lg"
                class="min-w-[280px] flex-1 max-w-sm"
            />
            <x-ui.button type="submit" variant="secondary" size="sm" icon="search">Search</x-ui.button>

            @if (array_filter($filters))
                <a href="{{ route('admin.audit-logs.index') }}" class="text-label-sm text-outline hover:text-primary transition-colors">Clear</a>
            @endif
        </form>

        {{-- Data Table --}}
        <x-ui.table :headers="['Action', 'Description', 'User', 'IP Address', 'Timestamp']">
            @forelse ($logs as $log)
                @php
                    $isDestructive = str_contains($log->action, 'deleted')
                        || str_contains($log->action, 'archived')
                        || str_contains($log->action, 'terminated')
                        || str_contains($log->action, 'deactivated');
                    $isMilestone = str_contains($log->action, 'activated')
                        || str_contains($log->action, 'published')
                        || str_contains($log->action, 'graded')
                        || str_contains($log->action, 'restored');
                    $isCreated = str_contains($log->action, 'created') || str_contains($log->action, 'imported');

                    $badgeVariant = match (true) {
                        $isDestructive => 'error',
                        $isMilestone => 'success',
                        $isCreated => 'primary',
                        default => 'neutral',
                    };
                @endphp
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-lg py-md">
                        <x-ui.badge :variant="$badgeVariant">{{ $log->action }}</x-ui.badge>
                    </td>
                    <td class="px-lg py-md text-body-md text-on-surface">{{ $log->description ?? '—' }}</td>
                    <td class="px-lg py-md text-label-md text-on-surface-variant">{{ $log->user?->name ?? 'System' }}</td>
                    <td class="px-lg py-md font-mono text-label-sm text-outline">{{ $log->ip_address ?? '—' }}</td>
                    <td class="px-lg py-md text-label-sm text-on-surface-variant whitespace-nowrap">{{ $log->created_at?->format('M d, Y h:i A') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <x-ui.empty-state
                            icon="history_edu"
                            title="No audit log entries found"
                            description="Actions taken by counselors and administrators will appear here."
                        />
                    </td>
                </tr>
            @endforelse

            <x-slot:footer>
                <x-ui.pagination
                    :current-page="$logs->currentPage()"
                    :total-pages="$logs->lastPage()"
                    :total-label="'Showing ' . $logs->firstItem() . '-' . $logs->lastItem() . ' of ' . $logs->total() . ' entries'"
                />
            </x-slot:footer>
        </x-ui.table>
    </x-ui.card>

</x-layouts.admin>
