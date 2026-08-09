<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Models\AuditLog;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function __construct(protected DatabaseBackupService $backups) {}

    public function index(): View
    {
        return view('admin.backup.index', [
            'backups' => $this->backups->list(),
        ]);
    }

    /**
     * Create a new backup of the entire database.
     */
    public function store(): RedirectResponse
    {
        $filename = $this->backups->create();

        $this->logAudit('backup.created', "Created database backup \"{$filename}\".");

        return redirect()
            ->route('admin.backup.index')
            ->with('status', "Backup \"{$filename}\" was created.");
    }

    public function download(string $filename): Response
    {
        return response()->download($this->backups->path($filename));
    }

    public function destroy(string $filename): RedirectResponse
    {
        $this->backups->path($filename); // 404s if it doesn't exist / rejects traversal

        $this->backups->delete($filename);

        $this->logAudit('backup.deleted', "Deleted database backup \"{$filename}\".");

        return redirect()
            ->route('admin.backup.index')
            ->with('status', "Backup \"{$filename}\" was deleted.");
    }

    /**
     * Restore the database from an uploaded .sql file. Overwrites all
     * current data — gated behind an explicit confirmation checkbox.
     */
    public function restore(RestoreBackupRequest $request): RedirectResponse
    {
        $uploaded = $request->file('backup_file');
        $tempPath = $uploaded->getRealPath();

        $this->backups->restore($tempPath);

        $this->logAudit('backup.restored', "Restored the database from an uploaded backup file (\"{$uploaded->getClientOriginalName()}\").");

        return redirect()
            ->route('admin.backup.index')
            ->with('status', 'Database restored successfully from the uploaded backup.');
    }

    protected function logAudit(string $action, string $description): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => null,
            'subject_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
