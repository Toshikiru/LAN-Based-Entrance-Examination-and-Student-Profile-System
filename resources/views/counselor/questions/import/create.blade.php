<x-layouts.counselor title="Import Questions" active="questions">

    <x-navigation.header
        title="Import Questions"
        subtitle="Upload a structured .txt, .docx, or .pdf file to add many questions at once."
    >
        <x-slot:breadcrumb>
            <x-ui.breadcrumb :items="[
                ['label' => 'Question Bank', 'href' => route('counselor.questions.index')],
                ['label' => 'Import'],
            ]" />
        </x-slot:breadcrumb>
    </x-navigation.header>

    @if (session('error'))
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        {{-- Upload zone --}}
        <div class="lg:col-span-2">
            {{-- data-turbo="false": Turbo intercepts every form submission and
                 replays it as a fetch() — for a multipart file upload this can
                 fail silently (no error, no navigation, nothing visibly
                 happens on click) with no event handler in this app surfacing
                 the failure. A real browser-native submission sidesteps that
                 entirely and is the same precedent already used for the
                 branding/logo upload form. --}}
            <form method="POST" action="{{ route('counselor.questions.import.preview') }}" enctype="multipart/form-data" data-turbo="false"
                  x-data="{
                      dragging: false,
                      fileName: '',
                      hasFile: false,
                      onChange(e) { const f = e.target.files[0]; this.fileName = f ? f.name : ''; this.hasFile = !!f; },
                      onDrop(e) { this.dragging = false; const f = e.dataTransfer.files[0]; if (f) { $refs.file.files = e.dataTransfer.files; this.fileName = f.name; this.hasFile = true; } }
                  }">
                @csrf

                <div
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="onDrop($event)"
                    x-on:click="$refs.file.click()"
                    :class="dragging ? 'border-primary bg-primary/5' : 'border-outline-variant'"
                    class="cursor-pointer border-2 border-dashed rounded-xl bg-surface-container-lowest p-xl flex flex-col items-center justify-center text-center gap-md min-h-[280px] transition-all"
                >
                    <div class="w-20 h-20 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-[40px]">cloud_upload</span>
                    </div>
                    <div>
                        <h3 class="font-headline-md text-headline-md text-on-surface">Drag &amp; drop your file here</h3>
                        <p class="text-body-md text-on-surface-variant mt-xs">Supported: <span class="font-semibold text-on-surface">.txt, .docx, .pdf</span> · up to 10 MB</p>
                    </div>

                    <span class="inline-flex items-center gap-sm px-lg py-3 rounded-full bg-primary-container text-on-primary-container font-label-md">
                        <span class="material-symbols-outlined text-[18px]">upload_file</span>
                        Browse Files
                    </span>

                    <p x-show="hasFile" x-cloak class="text-label-md text-secondary flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[18px]">description</span>
                        <span x-text="fileName"></span>
                    </p>
                </div>

                <input type="file" name="document" x-ref="file" x-on:change="onChange($event)" accept=".txt,.docx,.pdf" class="hidden" />

                <div class="flex items-center justify-end gap-md mt-lg">
                    <x-ui.button :href="route('counselor.questions.index')" variant="ghost">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon="visibility" x-bind:disabled="!hasFile" x-bind:class="!hasFile ? 'opacity-50 pointer-events-none' : ''">
                        Upload &amp; Preview
                    </x-ui.button>
                </div>
            </form>
        </div>

        {{-- Format help --}}
        <div>
            <x-ui.card>
                <div class="flex items-center justify-between mb-md">
                    <h3 class="font-headline-md text-headline-md text-on-surface">Import Format</h3>
                    <x-ui.button :href="route('counselor.questions.import.template')" data-turbo="false" variant="secondary" size="sm" icon="download">Template</x-ui.button>
                </div>
                <p class="text-body-md text-on-surface-variant mb-md">One question per block, separated by a blank line. Mark the correct choice with a trailing <span class="font-mono text-on-surface">*</span>.</p>

                <pre class="bg-surface-container-low rounded-lg p-md text-label-sm text-on-surface-variant overflow-x-auto custom-scrollbar leading-relaxed">TYPE: multiple_choice
CATEGORY: Reasoning
POINTS: 2
Q: If 2x = 10, x = ?
A) 3
B) 5 *
C) 8

TYPE: likert
Q: I enjoy teamwork.
- Agree = 4
- Neutral = 3
- Disagree = 2</pre>

                <p class="text-label-sm text-outline mt-md">Headers (TYPE, CATEGORY, DIFFICULTY, POINTS) are optional — the type is auto-detected when omitted. .docx and .pdf need the Composer packages noted in the docs.</p>
            </x-ui.card>
        </div>
    </div>

</x-layouts.counselor>
