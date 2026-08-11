@php use App\Enums\ExamSessionStatus; @endphp

<x-layouts.student title="Join Examination" active="exams">

    <x-navigation.header
        title="Join an Examination"
        subtitle="Enter the access code given by your guidance counselor."
    />

    @if (session('error'))
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- Access code --}}
        <div class="lg:col-span-1">
            <x-ui.card>
                <div class="text-center mb-lg">
                    <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-md">
                        <span class="material-symbols-outlined text-[36px]">login</span>
                    </div>
                    <h3 class="font-headline-md text-headline-md text-on-surface">Enter Access Code</h3>
                </div>

                <form method="POST" action="{{ route('student.exams.start') }}" class="space-y-md">
                    @csrf
                    <input
                        type="text"
                        name="access_code"
                        value="{{ old('access_code') }}"
                        placeholder="e.g. 4KP2ZQ"
                        autocomplete="off"
                        class="w-full text-center font-mono text-headline-md tracking-widest uppercase bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-4 outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all"
                        required
                    >
                    @error('access_code') <p class="text-label-sm text-error text-center">{{ $message }}</p> @enderror
                    <x-ui.button type="submit" icon="arrow_forward" class="w-full">Start Exam</x-ui.button>
                </form>
            </x-ui.card>
        </div>

        {{-- Previous / in-progress sessions --}}
        <div class="lg:col-span-2">
            <x-ui.card :padded="false">
                <div class="p-lg border-b border-outline-variant">
                    <h3 class="font-headline-md text-headline-md text-on-surface">My Examinations</h3>
                </div>
                @forelse ($sessions as $session)
                    <div class="flex items-center justify-between p-lg border-b border-outline-variant last:border-b-0">
                        <div>
                            <p class="font-label-md text-on-surface">{{ $session->exam->title }}</p>
                            <p class="text-label-sm text-outline">{{ $session->exam->duration_minutes ? $session->exam->duration_minutes . ' min' : 'No limit' }}</p>
                        </div>
                        @if ($session->status === ExamSessionStatus::Completed)
                            <x-ui.button :href="route('student.exams.result', $session->exam)" variant="outline" size="sm" icon="grade">View Result</x-ui.button>
                        @elseif ($session->status === ExamSessionStatus::InProgress)
                            <x-ui.button :href="route('student.exams.take', $session->exam)" size="sm" icon="play_arrow">Resume</x-ui.button>
                        @else
                            <x-ui.badge variant="neutral">Not started</x-ui.badge>
                        @endif
                    </div>
                @empty
                    <div class="p-xl">
                        <x-ui.empty-state icon="assignment" title="No examinations yet" description="Once you join an exam with an access code, it will appear here." />
                    </div>
                @endforelse
            </x-ui.card>
        </div>
    </div>

</x-layouts.student>
