@php
    use App\Enums\QuestionType;
    $currentAnswer = $answers->get($current->id);
    $total = $questions->count();
    $answeredCount = $answers->filter(fn ($a) => $a->selected_option_id || $a->answer_text)->count();
@endphp
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
{{-- Opts this page out of Turbo Drive entirely: every navigation into or
     within the live, timed exam-taking flow falls back to a plain full page
     load, exactly as it behaves today. This page has no sidebar/topnav to
     preserve (Turbo's main benefit doesn't apply here), and it's the most
     integrity-sensitive flow in the app — a countdown timer that
     auto-submits on expiry — so it isn't worth the added complexity of
     Turbo-safe timer teardown/reinit for a purely cosmetic smoothness gain. --}}
<meta name="turbo-visit-control" content="reload"/>
<title>{{ $exam->title }} | Exam</title>
@include('partials.theme-head')
</head>
<body class="bg-background text-on-background font-body-md min-h-screen">

{{-- Top bar: title + timer --}}
<header class="sticky top-0 z-40 bg-surface/90 backdrop-blur-md border-b border-outline-variant h-16 flex items-center justify-between px-lg">
    <div class="flex items-center gap-md min-w-0">
        <span class="material-symbols-outlined text-primary">quiz</span>
        <h1 class="font-headline-md text-headline-md text-on-surface truncate">{{ $exam->title }}</h1>
    </div>
    <div id="timer" class="flex items-center gap-sm px-lg py-sm rounded-lg bg-primary/10 text-primary font-headline-md tracking-wider"
         x-data="examTimer({{ $remaining }})" x-init="start()">
        <span class="material-symbols-outlined">timer</span>
        <span x-text="display"></span>
    </div>
</header>

<main class="max-w-[1100px] mx-auto w-full p-lg grid grid-cols-1 lg:grid-cols-4 gap-lg">
    {{-- Question --}}
    <div class="lg:col-span-3 space-y-lg">
        <div class="flex items-center justify-between">
            <p class="font-label-md text-on-surface-variant">Question {{ $index + 1 }} of {{ $total }}</p>
            <p class="text-label-sm text-outline">{{ $answeredCount }} answered</p>
        </div>
        <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
            <div class="h-full bg-primary transition-all" style="width: {{ $total ? ($answeredCount / $total) * 100 : 0 }}%"></div>
        </div>

        <form method="POST" action="{{ route('student.exams.answer', $exam) }}">
            @csrf
            <input type="hidden" name="question_id" value="{{ $current->id }}">
            <input type="hidden" name="current_index" value="{{ $index }}">

            <x-ui.card>
                <div class="flex items-start gap-md mb-lg">
                    <span class="w-8 h-8 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold shrink-0">{{ $index + 1 }}</span>
                    <p class="text-body-lg text-on-surface pt-1">{{ $current->question_text }}</p>
                </div>

                <div class="space-y-sm">
                    @if ($current->type === QuestionType::ShortAnswer)
                        <textarea name="answer_text" rows="5" placeholder="Type your answer..." autocomplete="off"
                                  class="w-full bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-3 text-body-md outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all resize-none">{{ $currentAnswer?->answer_text }}</textarea>
                    @else
                        @foreach ($options as $option)
                            <label class="flex items-center gap-md px-lg py-md border rounded-lg cursor-pointer transition-all {{ $currentAnswer?->selected_option_id === $option->id ? 'border-primary bg-primary/5' : 'border-outline-variant hover:border-primary/40' }}">
                                <input type="radio" name="selected_option_id" value="{{ $option->id }}"
                                       @checked($currentAnswer?->selected_option_id === $option->id)
                                       class="w-5 h-5 text-primary focus:ring-primary">
                                <span class="text-body-md text-on-surface">{{ $option->option_text }}</span>
                            </label>
                        @endforeach
                    @endif
                </div>
            </x-ui.card>

            {{-- Actions --}}
            <div class="flex items-center justify-between mt-lg gap-md flex-wrap">
                <div class="flex items-center gap-sm">
                    <button type="submit" name="nav" value="prev" @disabled($index === 0)
                            class="inline-flex items-center gap-sm px-lg py-3 rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container-low transition-colors disabled:opacity-40 disabled:pointer-events-none">
                        <span class="material-symbols-outlined text-[18px]">chevron_left</span> Previous
                    </button>
                    <button type="submit" name="nav" value="flag"
                            class="inline-flex items-center gap-sm px-lg py-3 rounded-lg border {{ $currentAnswer?->is_flagged ? 'border-error text-error' : 'border-outline-variant text-on-surface-variant' }} hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-[18px]">flag</span>
                        {{ $currentAnswer?->is_flagged ? 'Unflag' : 'Flag' }}
                    </button>
                </div>

                <div class="flex items-center gap-sm">
                    <button type="submit" name="nav" value="save"
                            class="inline-flex items-center gap-sm px-lg py-3 rounded-lg border border-primary text-primary hover:bg-primary/5 transition-colors">
                        <span class="material-symbols-outlined text-[18px]">save</span> Save
                    </button>
                    @if ($index + 1 < $total)
                        <button type="submit" name="nav" value="next"
                                class="inline-flex items-center gap-sm px-lg py-3 rounded-lg bg-primary text-on-primary hover:shadow-lg transition-all">
                            Next <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </button>
                    @else
                        <button type="submit" name="nav" value="submit"
                                onclick="return confirm('Submit your exam? You cannot change your answers afterwards.');"
                                class="inline-flex items-center gap-sm px-lg py-3 rounded-lg bg-secondary text-on-secondary hover:shadow-lg transition-all">
                            <span class="material-symbols-outlined text-[18px]">send</span> Submit
                        </button>
                    @endif
                </div>
            </div>

            {{-- Navigator (submits current answer, then jumps) --}}
            <div class="mt-xl">
                <p class="text-label-sm text-outline uppercase tracking-wider mb-sm">Question Navigator</p>
                <div class="flex flex-wrap gap-sm">
                    @foreach ($questions as $i => $q)
                        @php
                            $a = $answers->get($q->id);
                            $isCurrent = $i === $index;
                            $isAnswered = $a && ($a->selected_option_id || $a->answer_text);
                            $isFlagged = $a?->is_flagged;
                            $cls = $isCurrent ? 'bg-primary text-on-primary border-primary'
                                : ($isFlagged ? 'border-error text-error'
                                : ($isAnswered ? 'bg-secondary/10 text-secondary border-secondary/40' : 'border-outline-variant text-on-surface-variant'));
                        @endphp
                        <button type="submit" name="nav" value="goto:{{ $i }}"
                                class="w-10 h-10 rounded-lg border font-label-md {{ $cls }} hover:opacity-80 transition-all">{{ $i + 1 }}</button>
                    @endforeach
                </div>
                <div class="flex items-center gap-lg mt-md text-label-sm text-on-surface-variant">
                    <span class="flex items-center gap-xs"><span class="w-3 h-3 rounded bg-secondary/40"></span> Answered</span>
                    <span class="flex items-center gap-xs"><span class="w-3 h-3 rounded border border-error"></span> Flagged</span>
                    <span class="flex items-center gap-xs"><span class="w-3 h-3 rounded border border-outline-variant"></span> Unanswered</span>
                </div>
            </div>
        </form>
    </div>

    {{-- Submit panel --}}
    <div class="lg:col-span-1">
        <div class="sticky top-24 space-y-lg">
            <x-ui.card>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-md">Progress</h3>
                <div class="space-y-sm text-label-md">
                    <div class="flex justify-between"><span class="text-on-surface-variant">Answered</span><span class="text-on-surface font-semibold">{{ $answeredCount }} / {{ $total }}</span></div>
                    <div class="flex justify-between"><span class="text-on-surface-variant">Flagged</span><span class="text-on-surface font-semibold">{{ $answers->where('is_flagged', true)->count() }}</span></div>
                </div>
                <form method="POST" action="{{ route('student.exams.submit', $exam) }}" class="mt-lg"
                      onsubmit="return confirm('Submit your exam? You cannot change your answers afterwards.');">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" icon="send" class="w-full">Submit Exam</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>
</main>

{{-- Auto-submit on time expiry --}}
<form id="auto-submit-form" method="POST" action="{{ route('student.exams.submit', $exam) }}" class="hidden">@csrf</form>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('examTimer', (seconds) => ({
            remaining: seconds,
            display: '00:00',
            start() {
                this.render();
                const tick = setInterval(() => {
                    this.remaining--;
                    if (this.remaining <= 0) {
                        clearInterval(tick);
                        this.remaining = 0;
                        this.render();
                        document.getElementById('auto-submit-form').submit();
                        return;
                    }
                    this.render();
                }, 1000);
            },
            render() {
                const h = Math.floor(this.remaining / 3600);
                const m = Math.floor((this.remaining % 3600) / 60);
                const s = this.remaining % 60;
                const pad = (n) => String(n).padStart(2, '0');
                this.display = (h > 0 ? pad(h) + ':' : '') + pad(m) + ':' + pad(s);
            },
        }));
    });
</script>

</body>
</html>
