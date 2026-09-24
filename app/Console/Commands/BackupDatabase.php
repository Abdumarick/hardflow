<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'hardflow:backup {--keep= : Override retention in days}';

    protected $description = 'Create a checksummed backup of the HardFlow database';

    public function handle(): int
    {
        try {
            $path = $this->createBackup();
            $this->removeExpiredBackups((int) ($this->option('keep') ?? config('hardflow.backup.retention_days')));
            $this->info('Backup created: '.$path);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    public function createBackup(): string
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        throw_unless(in_array($driver, ['mysql', 'mariadb', 'sqlite'], true), RuntimeException::class, "Unsupported backup driver: {$driver}");

        $directory = config('hardflow.backup.path');
        File::ensureDirectoryExists($directory, 0700, true);
        $extension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $name = 'hardflow-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.'.$extension;
        $path = $directory.DIRECTORY_SEPARATOR.$name;

        $driver === 'sqlite' ? $this->backupSqlite($path) : $this->backupMysql($path);
        throw_unless(File::exists($path) && File::size($path) > 0, RuntimeException::class, 'The backup output is empty.');

        File::put($path.'.json', json_encode([
            'file' => $name,
            'driver' => $driver,
            'created_at' => now()->toIso8601String(),
            'bytes' => File::size($path),
            'sha256' => hash_file('sha256', $path),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $path;
    }

    private function backupSqlite(string $path): void
    {
        $quoted = str_replace("'", "''", str_replace('\\', '/', $path));
        DB::statement("VACUUM INTO '{$quoted}'");
    }

    private function backupMysql(string $path): void
    {
        $config = DB::connection()->getConfig();
        $process = new Process([
            config('hardflow.backup.mysqldump_binary'),
            '--single-transaction', '--routines', '--triggers', '--no-tablespaces',
            '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'],
            '--result-file='.$path, $config['database'],
        ], null, ['MYSQL_PWD' => (string) $config['password']]);
        $process->setTimeout(300)->mustRun();
    }

    private function removeExpiredBackups(int $days): void
    {
        if ($days < 1) {
            return;
        }
        foreach (File::glob(config('hardflow.backup.path').DIRECTORY_SEPARATOR.'hardflow-*.*') as $file) {
            if (File::lastModified($file) < now()->subDays($days)->timestamp) {
                File::delete($file);
            }
        }
    }
}
