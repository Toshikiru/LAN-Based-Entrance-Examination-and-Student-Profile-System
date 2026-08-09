@props([
    'type' => 'text', // text | card | table-row | avatar | stat-card
    'count' => 1,
])

@for ($i = 0; $i < $count; $i++)
    @if ($type === 'text')
        <div {{ $attributes->class(['h-4 rounded skeleton-shimmer w-full mb-sm last:mb-0']) }}></div>
    @elseif ($type === 'avatar')
        <div {{ $attributes->class(['w-10 h-10 rounded-full skeleton-shimmer']) }}></div>
    @elseif ($type === 'card')
        <div {{ $attributes->class(['bg-surface-container-lowest border border-outline-variant rounded-xl p-lg']) }}>
            <div class="h-5 w-1/3 rounded skeleton-shimmer mb-md"></div>
            <div class="h-4 w-full rounded skeleton-shimmer mb-sm"></div>
            <div class="h-4 w-2/3 rounded skeleton-shimmer"></div>
        </div>
    @elseif ($type === 'stat-card')
        <div {{ $attributes->class(['bg-surface-container-lowest border border-outline-variant rounded-2xl p-lg']) }}>
            <div class="w-10 h-10 rounded-lg skeleton-shimmer mb-md"></div>
            <div class="h-3 w-1/2 rounded skeleton-shimmer mb-sm"></div>
            <div class="h-6 w-1/3 rounded skeleton-shimmer"></div>
        </div>
    @elseif ($type === 'table-row')
        <tr {{ $attributes }}>
            <td class="px-4 py-4" colspan="100%">
                <div class="h-4 w-full rounded skeleton-shimmer"></div>
            </td>
        </tr>
    @endif
@endfor
