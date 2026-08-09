@props([
    'version' => 'v2.4.0',
])

<footer class="px-lg py-md border-t border-outline-variant bg-surface-container-lowest mt-auto">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-md text-outline text-label-sm">
        <p class="min-w-0 truncate max-w-full text-center sm:text-left" title="{{ $branding['system_name'] }} {{ $version }} · {{ $branding['school_name'] }}">&copy; {{ date('Y') }} {{ $branding['system_name'] }} {{ $version }} &bull; {{ $branding['school_name'] }}</p>
        <div class="flex items-center gap-xl shrink-0 flex-wrap justify-center">
            <div class="flex items-center gap-xs">
                <span class="w-2 h-2 rounded-full bg-secondary"></span>
                <span>Server: Operational</span>
            </div>
            <a class="hover:text-primary transition-colors" href="#">Privacy Policy</a>
            <a class="hover:text-primary transition-colors" href="#">Terms of Service</a>
        </div>
    </div>
</footer>
