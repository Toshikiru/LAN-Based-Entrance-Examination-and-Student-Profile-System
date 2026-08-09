@php
    $maxSignup = max(1, ...$signups['counts']);
@endphp

<x-layouts.admin title="Dashboard" active="dashboard">

    <x-navigation.header
        title="Welcome back, {{ auth()->user()->name }}"
        subtitle="System-wide overview of users, examinations, and audit activity."
    />

    {{-- Core Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-lg mb-lg">
        <x-ui.stat-card icon="school" label="Total Students" value="{{ number_format($stats['total_students']) }}" variant="primary" />
        <x-ui.stat-card icon="support_agent" label="Guidance Counselors" value="{{ number_format($stats['total_counselors']) }}" variant="secondary" />
        <x-ui.stat-card icon="assignment" label="Total Examinations" value="{{ number_format($stats['total_examinations']) }}" variant="tertiary" />
        <x-ui.stat-card icon="sensors" label="Active Sessions" value="{{ number_format($stats['active_sessions']) }}" variant="neutral" />
    </div>

    {{-- Users by Role + System Status --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg mb-xl">
        <x-ui.card title="Total Users by Role">
            <div class="flex items-end gap-xl mb-lg">
                <div>
                    <p class="text-[36px] font-black text-on-surface leading-none">{{ number_format($stats['total_users']) }}</p>
                    <p class="text-label-sm text-outline uppercase tracking-wide mt-xs">Total Users</p>
                </div>
            </div>

            @php
                $total = max(1, $stats['total_users']);
                $roles = [
                    ['label' => 'Super Admin', 'count' => $usersByRole['super_admin'], 'color' => 'bg-primary'],
                    ['label' => 'Counselors', 'count' => $usersByRole['counselor'], 'color' => 'bg-secondary-fixed-dim'],
                    ['label' => 'Students', 'count' => $usersByRole['student'], 'color' => 'bg-surface-variant'],
                ];
            @endphp

            <div class="flex h-2 w-full rounded-full overflow-hidden mb-md">
                @foreach ($roles as $role)
                    <div class="{{ $role['color'] }} h-full" style="width: {{ round(($role['count'] / $total) * 100, 2) }}%"></div>
                @endforeach
            </div>

            <div class="grid grid-cols-3 gap-md text-center">
                @foreach ($roles as $role)
                    <div>
                        <p class="font-headline-md text-headline-md text-on-surface">{{ number_format($role['count']) }}</p>
                        <p class="text-label-sm text-outline">{{ $role['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="System Status">
            <div class="space-y-sm">
                <div class="flex items-center justify-between p-md bg-surface-container-low rounded-lg">
                    <span class="font-label-md text-label-md text-on-surface-variant">Database</span>
                    @if ($systemStatus['database'])
                        <x-ui.badge variant="success" dot>Connected</x-ui.badge>
                    @else
                        <x-ui.badge variant="error" dot>Unreachable</x-ui.badge>
                    @endif
                </div>
                <div class="flex items-center justify-between p-md bg-surface-container-low rounded-lg">
                    <span class="font-label-md text-label-md text-on-surface-variant">Cache Driver</span>
                    <span class="font-label-md text-label-md text-on-surface font-mono">{{ $systemStatus['cache_driver'] }}</span>
                </div>
                <div class="flex items-center justify-between p-md bg-surface-container-low rounded-lg">
                    <span class="font-label-md text-label-md text-on-surface-variant">Queue Driver</span>
                    <span class="font-label-md text-label-md text-on-surface font-mono">{{ $systemStatus['queue_driver'] }}</span>
                </div>
                <div class="flex items-center justify-between p-md bg-surface-container-low rounded-lg">
                    <span class="font-label-md text-label-md text-on-surface-variant">Environment</span>
                    <x-ui.badge :variant="$systemStatus['environment'] === 'production' ? 'success' : 'warning'">{{ ucfirst($systemStatus['environment']) }}</x-ui.badge>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- Main Column --}}
        <div class="lg:col-span-2 space-y-xl">
            {{-- System Overview Chart --}}
            <x-ui.card title="System Overview" subtitle="New user registrations per week, last 8 weeks.">
                <div class="flex items-end gap-md h-[200px] pt-md">
                    @foreach ($signups['labels'] as $i => $label)
                        <div class="flex-1 flex flex-col items-center gap-sm h-full justify-end group">
                            <span class="text-[11px] font-bold text-on-surface-variant opacity-0 group-hover:opacity-100 transition-opacity">{{ $signups['counts'][$i] }}</span>
                            <div class="w-full bg-primary/20 hover:bg-primary/30 rounded-t-lg transition-colors" style="height: {{ max(4, round(($signups['counts'][$i] / $maxSignup) * 100)) }}%;"></div>
                            <span class="text-[10px] font-bold text-outline uppercase">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            {{-- Recent User Logins --}}
            <x-ui.card title="Recent User Logins" :padded="false">
                <x-ui.table :headers="['User', 'Role', 'Last Login']">
                    @forelse ($recentLogins as $user)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-md">
                                    <div class="w-9 h-9 rounded-full bg-primary-fixed text-primary flex items-center justify-center font-bold text-label-sm shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-label-md text-label-md text-on-surface">{{ $user->name }}</p>
                                        <p class="text-label-sm text-outline">{{ $user->email ?? $user->school_id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <x-ui.badge variant="neutral">{{ $user->role->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-4 text-on-surface-variant">{{ $user->last_login_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-4">
                                <x-ui.empty-state icon="login" title="No logins recorded yet" description="Authenticated sessions will appear here once users start signing in." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>

        {{-- Right Sidebar Column --}}
        <div class="space-y-xl">
            <x-ui.card title="Quick Actions">
                <div class="space-y-md">
                    <a href="{{ route('admin.administrators.index') }}" class="w-full flex items-center gap-md p-md bg-primary-fixed hover:bg-primary-fixed-dim transition-colors rounded-xl group">
                        <div class="w-10 h-10 rounded-lg bg-primary text-on-primary flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                            <span class="material-symbols-outlined">manage_accounts</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-primary-fixed font-bold leading-tight">Manage Administrators</p>
                            <p class="text-xs text-on-primary-fixed-variant opacity-80">View, add, or deactivate accounts</p>
                        </div>
                    </a>
                    <a href="{{ route('admin.administrators.create') }}" class="w-full flex items-center gap-md p-md bg-surface-container hover:bg-surface-container-high transition-colors rounded-xl group">
                        <div class="w-10 h-10 rounded-lg bg-on-surface-variant text-surface-container-lowest flex items-center justify-center group-hover:scale-110 transition-transform shrink-0">
                            <span class="material-symbols-outlined">person_add</span>
                        </div>
                        <div>
                            <p class="font-label-md text-label-md text-on-surface font-bold leading-tight">Add Administrator</p>
                            <p class="text-xs text-outline">Create a new guidance office account</p>
                        </div>
                    </a>
                </div>
            </x-ui.card>

            <x-ui.card title="Recent Audit Logs">
                @if ($recentAuditLogs->isEmpty())
                    <x-ui.empty-state
                        icon="history_edu"
                        title="No audit log data yet"
                        description="Audit log entries will appear here once counselor or admin actions are recorded."
                    />
                @else
                    <div class="space-y-lg relative">
                        <div class="absolute left-[19px] top-2 bottom-2 w-px bg-outline-variant"></div>
                        @foreach ($recentAuditLogs as $log)
                            <div class="relative flex gap-lg z-10">
                                <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant ring-4 ring-surface-container-lowest shrink-0">
                                    <span class="material-symbols-outlined text-[18px]">history</span>
                                </div>
                                <div>
                                    <p class="font-label-md text-label-md text-on-surface">{{ $log->user?->name ?? 'System' }}</p>
                                    <p class="text-sm text-outline-v riant mb-1 font-medium">{{ $log->description }}</p>
                                    <p class="text-[11px] text-outline">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div> 

</x-layouts.admin>
