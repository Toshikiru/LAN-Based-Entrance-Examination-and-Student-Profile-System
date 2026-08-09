@php
    $formatSize = function (int $bytes): string {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    };
@endphp

<x-layouts.admin title="Backup & Restore" active="backup">

    <x-navigation.header
        title="Backup & Restore"
        subtitle="Create downloadable snapshots of the entire database, or restore from a previous one."
    >
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.backup.store') }}">
                @csrf
                <x-ui.button type="submit" icon="backup">Create Backup Now</x-ui.button>
            </form>
        </x-slot:actions>
    </x-navigation.header>

    @if (session('status'))
        <x-ui.alert variant="success" dismissible class="mb-lg">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" dismissible class="mb-lg">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        <div class="lg:col-span-2">
            <x-ui.card title="Available Backups" subtitle="Stored privately on the server — never publicly accessible." :padded="false">
                <x-ui.table :headers="['Filename', 'Created', 'Size', '']">
                    @forelse ($backups as $backup)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="px-lg py-md font-label-md text-label-md text-on-surface font-mono">{{ $backup['filename'] }}</td>
                            <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $backup['created_at']->format('M d, Y h:i A') }}</td>
                            <td class="px-lg py-md font-label-md text-label-md text-on-surface-variant">{{ $formatSize($backup['size']) }}</td>
                            <td class="px-lg py-md text-right whitespace-nowrap">
                                <a href="{{ route('admin.backup.download', $backup['filename']) }}" data-turbo="false" class="p-2 inline-flex text-outline-variant hover:text-primary transition-colors" title="Download">
                                    <span class="material-symbols-outlined text-[20px]">download</span>
                                </a>
                                <button type="button" class="p-2 text-outline-variant hover:text-error transition-colors" title="Delete" @click="$dispatch('open-modal-delete-{{ $loop->index }}')">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-ui.empty-state icon="backup" title="No backups yet" description="Create your first backup to see it listed here." />
                            </td>
                        </tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>

        <div>
            <x-ui.card title="Restore from Backup">
                <x-ui.alert variant="error" icon="warning" class="mb-lg">
                    Restoring will <strong>permanently overwrite all current data</strong> with the contents of the uploaded file. This cannot be undone. Create a fresh backup first if you're unsure.
                </x-ui.alert>

                <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data" class="space-y-lg" onsubmit="return confirm('This will overwrite ALL current data. Are you absolutely sure?');">
                    @csrf
                    <x-ui.input label="Backup File (.sql)" name="backup_file" type="file" accept=".sql" required />

                    <label class="flex items-start gap-sm text-label-md text-on-surface-variant">
                        <input type="checkbox" name="confirm" value="1" required class="mt-1">
                        I understand this will overwrite all current data and cannot be undone.
                    </label>

                    <x-ui.button type="submit" variant="danger-solid" icon="restore" class="w-full">Restore Database</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>

    {{-- Per-row delete modals --}}
    @foreach ($backups as $index => $backup)
        <x-ui.modal name="delete-{{ $index }}" max-width="sm">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-error-container rounded-full flex items-center justify-center mb-md">
                    <span class="material-symbols-outlined text-error text-[36px]">delete</span>
                </div>
                <h3 class="font-headline-md text-headline-md text-on-surface mb-sm">Delete Backup?</h3>
                <p class="text-body-md text-on-surface-variant leading-relaxed">
                    <span class="font-bold text-on-surface font-mono">{{ $backup['filename'] }}</span> will be permanently deleted.
                </p>
            </div>

            <x-slot:footer>
                <x-ui.button variant="outline" class="flex-1" @click="open = false">Cancel</x-ui.button>
                <form method="POST" action="{{ route('admin.backup.destroy', $backup['filename']) }}" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger-solid" icon="delete" class="w-full">Delete</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach

</x-layouts.admin>
