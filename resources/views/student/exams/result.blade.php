<x-layouts.student title="Exam Result" active="exams">

    <x-navigation.header
        title="Submission Summary"
        :subtitle="$exam->title"
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'My Exams', 'href' => route('student.exams.join')],
                ['label' => 'Result'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    <div class="max-w-2xl mx-auto">
        <x-ui.card>
            <div class="text-center py-lg">
                <div class="w-20 h-20 rounded-full bg-secondary/10 text-secondary flex items-center justify-center mx-auto mb-md">
                    <span class="material-symbols-outlined text-[44px]">task_alt</span>
                </div>
                <h2 class="font-headline-md text-headline-md text-on-surface">Exam Submitted</h2>
                <p class="text-body-md text-on-surface-variant mt-xs">
                    {{ $session->submitted_at?->format('M d, Y h:i A') }}
                    @if ($session->auto_submitted) · auto-submitted at time expiry @endif
                </p>
            </div>

            @if (! $exam->show_score)
                <x-ui.alert variant="info" icon="schedule">
                    Your responses have been recorded. Results will be released by the guidance office.
                </x-ui.alert>
            @elseif ($pendingManual)
                <x-ui.alert variant="warning" icon="hourglass_top" class="mb-lg">
                    This is a preliminary result. It includes your automatically scored items only — short-answer items are still being graded by the guidance counselor.
                </x-ui.alert>
                @include('student.exams._score', ['result' => $result, 'exam' => $exam, 'preliminary' => true])
            @elseif ($result)
                @include('student.exams._score', ['result' => $result, 'exam' => $exam, 'preliminary' => false])
            @endif

            <div class="flex justify-center mt-xl">
                <x-ui.button :href="route('student.exams.join')" variant="outline" icon="arrow_back">Back to My Exams</x-ui.button>
            </div>
        </x-ui.card>
    </div>

</x-layouts.student>
