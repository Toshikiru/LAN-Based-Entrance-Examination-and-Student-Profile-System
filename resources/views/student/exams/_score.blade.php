@php
    $pct = (float) $result->percentage;
    $passed = (bool) $result->passed;
    $interpretation = $exam->interpretationFor($pct);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-lg my-lg">
    <div class="text-center px-lg py-md rounded-lg bg-surface-container-low">
        <p class="text-label-sm text-outline uppercase tracking-wider">Score</p>
        <p class="font-display-lg-mobile text-on-surface mt-xs">{{ rtrim(rtrim(number_format($result->total_score, 2), '0'), '.') }}<span class="text-headline-md text-outline">/{{ rtrim(rtrim(number_format($result->max_score, 2), '0'), '.') }}</span></p>
    </div>
    <div class="text-center px-lg py-md rounded-lg bg-surface-container-low">
        <p class="text-label-sm text-outline uppercase tracking-wider">Percentage</p>
        <p class="font-display-lg-mobile text-primary mt-xs">{{ rtrim(rtrim(number_format($pct, 2), '0'), '.') }}%</p>
    </div>
    <div class="text-center px-lg py-md rounded-lg {{ $passed ? 'bg-secondary/10' : 'bg-error-container/40' }}">
        <p class="text-label-sm text-outline uppercase tracking-wider">Result</p>
        <p class="font-display-lg-mobile mt-xs {{ $passed ? 'text-secondary' : 'text-error' }}">{{ $passed ? 'Passed' : 'Failed' }}</p>
    </div>
</div>

@unless ($preliminary)
    @if ($interpretation)
        <div class="text-center mb-md">
            <span class="inline-flex items-center gap-xs px-lg py-sm rounded-full bg-primary/10 text-primary font-label-md">
                <span class="material-symbols-outlined text-[18px]">workspace_premium</span>
                {{ $interpretation }}
            </span>
        </div>
    @endif
    <p class="text-center text-label-sm text-outline">
        Interpretation is based on the passing score of {{ rtrim(rtrim(number_format($exam->passing_score, 2), '0'), '.') }}%.
    </p>
@endunless
