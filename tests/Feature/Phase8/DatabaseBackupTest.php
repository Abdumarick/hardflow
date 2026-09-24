<?php

namespace Tests\Feature\Phase8;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    private string $backupDirectory;

    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDirectory = storage_path('framework/testing/hardflow-backups-'.bin2hex(random_bytes(4)));
        $this->databasePath = storage_path('framework/testing/hardflow-backup-source-'.bin2hex(random_bytes(4)).'.sqlite');
        File::put($this->databasePath, '');
        config([
            'database.default' => 'backup_test',
            'database.connections.backup_test' => [
                'driver' => 'sqlite',
                'database' => $this->databasePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'hardflow.backup.path' => $this->backupDirectory,
        ]);
        DB::purge('backup_test');
        DB::connection('backup_test')->statement('CREATE TABLE backup_probe (id INTEGER PRIMARY KEY, value TEXT)');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDirectory);
        DB::purge('backup_test');
        File::delete($this->databasePath);
        parent::tearDown();
    }

    public function test_backup_command_creates_database_and_checksum_manifest(): void
    {
        $this->artisan('hardflow:backup', ['--keep' => 1])->assertSuccessful();

        $backups = File::glob($this->backupDirectory.DIRECTORY_SEPARATOR.'*.sqlite');
        $this->assertCount(1, $backups);
        $this->assertFileExists($backups[0].'.json');
        $manifest = json_decode(File::get($backups[0].'.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(hash_file('sha256', $backups[0]), $manifest['sha256']);
        $this->assertSame('sqlite', $manifest['driver']);
    }

    public function test_restore_rejects_a_tampered_backup_before_replacing_the_database(): void
    {
        $this->artisan('hardflow:backup')->assertSuccessful();
        $backup = File::glob($this->backupDirectory.DIRECTORY_SEPARATOR.'*.sqlite')[0];
        File::append($backup, 'tampered');

        $this->artisan('hardflow:restore', ['backup' => basename($backup), '--force' => true])
            ->expectsOutputToContain('checksum does not match')
            ->assertFailed();
    }

    public function test_restore_rejects_paths_outside_the_backup_directory(): void
    {
        $this->artisan('hardflow:restore', ['backup' => '../database.sqlite', '--force' => true])
            ->expectsOutputToContain('filename, not a path')
            ->assertFailed();
    }

    public function test_restore_returns_sqlite_to_the_backed_up_state_and_keeps_a_safety_copy(): void
    {
        DB::table('backup_probe')->insert(['value' => 'kept']);
        $this->artisan('hardflow:backup')->assertSuccessful();
        $backup = File::glob($this->backupDirectory.DIRECTORY_SEPARATOR.'*.sqlite')[0];
        DB::table('backup_probe')->insert(['value' => 'removed by restore']);

        $this->artisan('hardflow:restore', ['backup' => basename($backup), '--force' => true])
            ->assertSuccessful();

        DB::purge('backup_test');
        $this->assertSame(['kept'], DB::connection('backup_test')->table('backup_probe')->pluck('value')->all());
        $this->assertCount(2, File::glob($this->backupDirectory.DIRECTORY_SEPARATOR.'*.sqlite'));
    }
}
