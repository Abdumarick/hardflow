<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class RestoreDatabase extends Command
{
    protected $signature = 'hardflow:restore {backup : Backup filename from the configured backup directory} {--force : Skip interactive confirmation}';

    protected $description = 'Verify and restore a HardFlow database backup after creating a safety backup';

    public function handle(BackupDatabase $backups): int
    {
        try {
            $path = $this->resolveBackup((string) $this->argument('backup'));
            $manifest = $this->verify($path);
            if (! $this->option('force') && ! $this->confirm('This replaces the current database. Continue?')) {
                $this->warn('Restore cancelled.');

                return self::SUCCESS;
            }

            $safetyPath = $backups->createBackup();
            $this->restore($path, $manifest['driver']);
            $this->info('Restore completed. Pre-restore backup: '.$safetyPath);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Restore failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveBackup(string $name): string
    {
        throw_if($name !== basename($name), RuntimeException::class, 'Provide a backup filename, not a path.');
        $path = config('hardflow.backup.path').DIRECTORY_SEPARATOR.$name;
        throw_unless(File::isFile($path), RuntimeException::class, 'Backup file not found.');

        return $path;
    }

    private function verify(string $path): array
    {
        $manifestPath = $path.'.json';
        throw_unless(File::isFile($manifestPath), RuntimeException::class, 'Backup manifest not found.');
        $manifest = json_decode(File::get($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        throw_unless(hash_equals((string) ($manifest['sha256'] ?? ''), hash_file('sha256', $path)), RuntimeException::class, 'Backup checksum does not match.');
        throw_unless(($manifest['driver'] ?? null) === DB::connection()->getDriverName(), RuntimeException::class, 'Backup driver does not match the active database.');

        return $manifest;
    }

    private function restore(string $path, string $driver): void
    {
        if ($driver === 'sqlite') {
            $database = DB::connection()->getDatabaseName();
            DB::disconnect();
            throw_unless(File::copy($path, $database), RuntimeException::class, 'Unable to replace the SQLite database.');
            DB::reconnect();

            return;
        }

        $config = DB::connection()->getConfig();
        $stream = fopen($path, 'rb');
        throw_unless(is_resource($stream), RuntimeException::class, 'Unable to read the backup file.');
        $process = new Process([
            config('hardflow.backup.mysql_binary'), '--host='.$config['host'], '--port='.$config['port'],
            '--user='.$config['username'], $config['database'],
        ], null, ['MYSQL_PWD' => (string) $config['password']]);
        $process->setInput($stream)->setTimeout(300)->mustRun();
        fclose($stream);
    }
}
