<x-layouts.admin title="System Settings" active="settings">

    <x-ui.breadcrumb class="mb-lg" :items="[
        ['label' => 'System Configuration'],
    ]" />

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-md mb-lg">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">System Settings</h2>
            <p class="text-body-md text-on-surface-variant">Manage your institution's core identity and academic configuration.</p>
        </div>
        <x-ui.button :href="route('admin.reference-data.index')" variant="outline" icon="storage">
            Manage Reference Data
        </x-ui.button>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: '{{ $tab }}' }">
        <nav class="flex gap-xl border-b border-outline-variant mb-lg">
            <button type="button" @click="tab = 'school-info'" :class="tab === 'school-info' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md transition-colors flex items-center gap-xs">
                <span class="material-symbols-outlined text-[20px]">school</span> School Information
            </button>
            <button type="button" @click="tab = 'branding'" :class="tab === 'branding' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md transition-colors flex items-center gap-xs">
                <span class="material-symbols-outlined text-[20px]">palette</span> Branding
            </button>
            <button type="button" @click="tab = 'academic-year'" :class="tab === 'academic-year' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md transition-colors flex items-center gap-xs">
                <span class="material-symbols-outlined text-[20px]">calendar_today</span> Academic Year
            </button>
            <button type="button" @click="tab = 'general'" :class="tab === 'general' ? 'text-primary font-bold border-b-2 border-primary' : 'text-on-surface-variant hover:text-primary'" class="pb-md font-label-md text-label-md transition-colors flex items-center gap-xs">
                <span class="material-symbols-outlined text-[20px]">settings_suggest</span> General Settings
            </button>
        </nav>

        {{-- School Information --}}
        <div x-show="tab === 'school-info'" x-cloak>
            {{-- data-turbo="false": School Name / System Display Name feed the sidebar's
                 brand text, which is permanently-mounted (see sidebar.blade.php) — a soft
                 Turbo visit wouldn't pick up the change, so this forces a real reload. --}}
            <form method="POST" action="{{ route('admin.settings.school-info.update') }}" data-turbo="false">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-lg">
                    <div class="lg:col-span-8 space-y-lg">
                        <x-ui.card title="Institution Identity">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                                <x-ui.input name="school_name" label="School Name" :value="old('school_name', $schoolInfo['school_name'])" autocomplete="organization" />
                                <div>
                                    <x-ui.input name="system_display_name" label="System Name" :value="old('system_display_name', $schoolInfo['system_display_name'])" />
                                    <p class="text-[11px] text-outline mt-1">Short name shown in the sidebar, browser tab, and login page (e.g. "TPC EntryPoint").</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="system_full_name" class="block font-label-md text-label-md text-on-surface-variant mb-xs">System Full Name</label>
                                    <textarea id="system_full_name" name="system_full_name" rows="2" autocomplete="off" class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-3 text-body-md outline-none transition-all resize-none focus:border-primary focus:ring-4 focus:ring-primary/10">{{ old('system_full_name', $schoolInfo['system_full_name']) }}</textarea>
                                    <p class="text-[11px] text-outline mt-1">Longer descriptive name shown on the login page (e.g. "LAN-Based Entrance Examination and Student Profile System for Guidance Services").</p>
                                    @error('system_full_name') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <x-ui.input name="short_code" label="Short Code" :value="old('short_code', $schoolInfo['short_code'])" />
                                    <p class="text-[11px] text-outline mt-1">Used for record indexing and document references.</p>
                                </div>
                            </div>
                        </x-ui.card>

                        <x-ui.card title="Contact Information">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                                <x-ui.input type="email" name="official_email" label="Official Email" :value="old('official_email', $schoolInfo['official_email'])" autocomplete="email" />
                                <x-ui.input type="tel" name="phone_number" label="Phone Number" :value="old('phone_number', $schoolInfo['phone_number'])" autocomplete="tel" />
                                <div class="md:col-span-2">
                                    <label for="mailing_address" class="block font-label-md text-label-md text-on-surface-variant mb-xs">Mailing Address</label>
                                    <textarea id="mailing_address" name="mailing_address" rows="3" autocomplete="street-address" class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-3 text-body-md outline-none transition-all resize-none focus:border-primary focus:ring-4 focus:ring-primary/10">{{ old('mailing_address', $schoolInfo['mailing_address']) }}</textarea>
                                    @error('mailing_address') <p class="text-label-sm text-error mt-xs">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </x-ui.card>
                    </div>

                    <div class="lg:col-span-4 space-y-lg">
                        <div class="bg-primary/5 rounded-xl p-lg border border-primary/20">
                            <div class="flex items-start gap-md">
                                <span class="material-symbols-outlined text-primary mt-xs">lightbulb</span>
                                <div>
                                    <h4 class="font-label-md text-primary mb-xs">Pro Tip</h4>
                                    <p class="text-body-md text-on-surface-variant leading-tight">This information appears on printed cumulative records and examination reports. Keep it accurate and up to date.</p>
                                </div>
                            </div>
                        </div>

                        <x-ui.card title="Reference Data">
                            <p class="text-body-md text-on-surface-variant mb-md">Departments, courses, and year levels used throughout the system.</p>
                            <x-ui.button :href="route('admin.reference-data.index')" variant="outline" icon="storage" class="w-full">
                                Open Reference Data
                            </x-ui.button>
                        </x-ui.card>
                    </div>
                </div>

                <div class="flex justify-end mt-lg">
                    <x-ui.button type="submit" icon="save">Save Changes</x-ui.button>
                </div>
            </form>
        </div>

        {{-- Branding --}}
        <div x-show="tab === 'branding'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
                <x-ui.card title="School Logo" subtitle="Shown in the sidebar and on the login page.">
                    <div class="flex items-center gap-lg mb-lg">
                        <div @class([
                            'w-20 h-20 rounded-lg flex items-center justify-center shrink-0',
                            'bg-surface-container-low border border-outline-variant' => $branding['logo_url'],
                            'bg-primary text-on-primary' => ! $branding['logo_url'],
                        ])>
                            @if ($branding['logo_url'])
                                <img src="{{ $branding['logo_url'] }}" alt="Current logo" class="max-w-full max-h-full w-auto h-auto object-contain p-sm">
                            @else
                                <span class="material-symbols-outlined text-[32px]">school</span>
                            @endif
                        </div>
                        <p class="text-label-sm text-outline">
                            @if ($branding['logo_url'])
                                Custom logo is currently active.
                            @else
                                Using the default {{ $branding['system_name'] }} icon. Upload an image to replace it.
                            @endif
                        </p>
                    </div>

                    {{-- data-turbo="false": the sidebar logo is a permanently-mounted
                         element (see sidebar.blade.php) that only re-reads server data
                         on a real page load, not a soft Turbo visit. --}}
                    <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" data-turbo="false" class="space-y-md">
                        @csrf
                        <x-ui.input type="file" name="logo" label="Upload New Logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" />
                        <p class="text-[11px] text-outline">PNG, JPG, SVG, or WEBP. Square images work best. Max 2MB.</p>
                        <x-ui.button type="submit" icon="upload" size="sm">Save Logo</x-ui.button>
                    </form>

                    @if ($branding['logo_url'])
                        <form method="POST" action="{{ route('admin.settings.branding.logo.destroy') }}" data-turbo="false" class="mt-sm">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm" icon="delete">Remove Logo</x-ui.button>
                        </form>
                    @endif
                </x-ui.card>

                <x-ui.card title="Browser Favicon" subtitle="Shown in the browser tab across the whole application.">
                    <div class="flex items-center gap-lg mb-lg">
                        <div class="w-20 h-20 rounded-lg bg-surface-container-high flex items-center justify-center text-on-surface-variant shrink-0 overflow-hidden">
                            @if ($branding['favicon_url'])
                                <img src="{{ $branding['favicon_url'] }}" alt="Current favicon" class="w-10 h-10 object-contain">
                            @else
                                <span class="material-symbols-outlined text-[32px]">tab</span>
                            @endif
                        </div>
                        <p class="text-label-sm text-outline">
                            @if ($branding['favicon_url'])
                                Custom favicon is currently active.
                            @else
                                Using the default application favicon. Upload an image to replace it.
                            @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" data-turbo="false" class="space-y-md">
                        @csrf
                        <x-ui.input type="file" name="favicon" label="Upload New Favicon" accept="image/x-icon,image/png,image/jpeg,image/svg+xml" />
                        <p class="text-[11px] text-outline">ICO, PNG, JPG, or SVG. A small square image (e.g. 32&times;32 or 512&times;512). Max 512KB.</p>
                        <x-ui.button type="submit" icon="upload" size="sm">Save Favicon</x-ui.button>
                    </form>

                    @if ($branding['favicon_url'])
                        <form method="POST" action="{{ route('admin.settings.branding.favicon.destroy') }}" data-turbo="false" class="mt-sm">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm" icon="delete">Remove Favicon</x-ui.button>
                        </form>
                    @endif
                </x-ui.card>
            </div>
        </div>

        {{-- Academic Year (not yet specified) --}}
        <div x-show="tab === 'academic-year'" x-cloak>
            <x-ui.card>
                <x-ui.empty-state
                    icon="calendar_today"
                    title="Academic Year management coming soon"
                    description="Term and academic-year configuration will be added in a future update."
                />
            </x-ui.card>
        </div>

        {{-- General Settings (not yet specified) --}}
        <div x-show="tab === 'general'" x-cloak>
            <x-ui.card>
                <x-ui.empty-state
                    icon="settings_suggest"
                    title="General Settings coming soon"
                    description="Additional system-wide preferences will be added in a future update."
                />
            </x-ui.card>
        </div>
    </div>

</x-layouts.admin>
