<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Pure-PHP MySQL dump/restore for the Super Admin's Backup & Restore page.
 *
 * Deliberately avoids shelling out to `mysqldump`/`mysql` binaries — this
 * app runs on LAN-only school deployments where the correct binary path
 * can't be assumed, so everything here goes through the DB facade instead.
 * Backups are stored on the private `local` disk (never `public`), since a
 * dump contains password hashes and other sensitive data.
 */
class DatabaseBackupService
{
    protected string $directory;

    public function __construct(protected SchoolSettingsService $settings)
    {
        $this->directory = storage_path('app/private/backups');

        if (! File::isDirectory($this->directory)) {
            File::makeDirectory($this->directory, 0755, true);
        }
    }

    /**
     * List available backup files, newest first.
     *
     * @return Collection<int, array{filename: string, size: int, created_at: \Carbon\Carbon}>
     */
    public function list(): Collection
    {
        return collect(File::files($this->directory))
            ->filter(fn ($file) => $file->getExtension() === 'sql')
            ->map(fn ($file) => [
                'filename' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => \Illuminate\Support\Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Dump every table's schema + data to a new timestamped .sql file.
     */
    public function create(): string
    {
        $filename = 'backup-' . now()->format('Y-m-d_His') . '.sql';
        $path = $this->directory . DIRECTORY_SEPARATOR . $filename;

        $handle = fopen($path, 'w');

        $systemName = $this->settings->branding()['system_name'];
        fwrite($handle, "-- {$systemName} database backup\n");
        fwrite($handle, '-- Generated ' . now()->toDateTimeString() . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($this->tableNames() as $table) {
            $createRow = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $createSql = $createRow['Create Table'] ?? reset($createRow);

            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createSql . ";\n\n");

            $this->writeTableData($handle, $table);
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return $filename;
    }

    /**
     * Replay a previously created (or uploaded) .sql file against the database.
     */
    public function restore(string $absolutePath): void
    {
        $sql = File::get($absolutePath);

        // Strip whole-line `-- comments` before splitting into statements —
        // otherwise a leading comment line (which never ends in `;`) merges
        // with the next real statement and both get treated as one, which
        // would silently swallow the very first statement after any comment
        // (e.g. `SET FOREIGN_KEY_CHECKS=0;` right after the file's header).
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);

        // Deliberately not wrapped in DB::transaction(): MySQL's DROP/CREATE
        // TABLE statements each cause an implicit commit, so a surrounding
        // transaction can never be atomic here anyway — it would only fail
        // later with "there is no active transaction" once MySQL closes it
        // out from under Laravel.
        foreach ($this->splitStatements($sql) as $statement) {
            if ($statement === '') {
                continue;
            }

            DB::unprepared($statement . ';');
        }
    }

    public function delete(string $filename): void
    {
        File::delete($this->path($filename));
    }

    /**
     * Resolve a backup filename to its absolute path, rejecting anything
     * that isn't a plain filename already present in the backups directory
     * (defends against path traversal from user-supplied input).
     */
    public function path(string $filename): string
    {
        $safeName = basename($filename);
        $path = $this->directory . DIRECTORY_SEPARATOR . $safeName;

        abort_unless(File::exists($path), 404);

        return $path;
    }

    // ---- Helpers ----

    protected function tableNames(): array
    {
        $database = DB::getDatabaseName();
        $column = "Tables_in_{$database}";

        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => ((array) $row)[$column])
            ->all();
    }

    /**
     * @param  resource  $handle
     */
    protected function writeTableData($handle, string $table): void
    {
        $pdo = DB::connection()->getPdo();
        $columns = null;
        $batch = [];
        $batchSize = 300;

        DB::table($table)->cursor()->each(function ($row) use (&$columns, &$batch, $batchSize, $handle, $table, $pdo) {
            $row = (array) $row;

            if ($columns === null) {
                $columns = array_keys($row);
            }

            $values = array_map(
                fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                $row
            );

            $batch[] = '(' . implode(',', $values) . ')';

            if (count($batch) >= $batchSize) {
                $this->flushBatch($handle, $table, $columns, $batch);
                $batch = [];
            }
        });

        if (! empty($batch)) {
            $this->flushBatch($handle, $table, $columns ?? [], $batch);
        }

        fwrite($handle, "\n");
    }

    /**
     * @param  resource  $handle
     */
    protected function flushBatch($handle, string $table, array $columns, array $rows): void
    {
        $columnList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
        fwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $rows) . ";\n");
    }

    /**
     * Split a multi-statement SQL string on `;`, respecting quoted strings
     * (including embedded newlines/semicolons inside text content) so a
     * naive split never corrupts a statement.
     *
     * @return array<int, string>
     */
    protected function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $quoteChar = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($inString) {
                if ($char === '\\' && $i + 1 < $length) {
                    $buffer .= $sql[++$i];

                    continue;
                }

                if ($char === $quoteChar) {
                    $inString = false;
                }

                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $inString = true;
                $quoteChar = $char;

                continue;
            }

            if ($char === ';') {
                $trimmed = trim(substr($buffer, 0, -1));

                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }

                $buffer = '';
            }
        }

        $trailing = trim($buffer);
        if ($trailing !== '') {
            $statements[] = $trailing;
        }

        return $statements;
    }
}
