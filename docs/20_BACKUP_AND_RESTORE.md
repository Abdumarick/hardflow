# HardFlow Backup and Restore Runbook

## Scope

HardFlow supports checksummed SQLite and MySQL/MariaDB database backups. Backups are private server files and must also be copied to encrypted off-site storage by the system operator.

## Configuration

Set these production environment values:

```dotenv
HARDFLOW_BACKUP_RETENTION_DAYS=30
HARDFLOW_MYSQLDUMP_PATH=C:\path\to\mysql\bin\mysqldump.exe
HARDFLOW_MYSQL_PATH=C:\path\to\mysql\bin\mysql.exe
```

Linux servers can normally retain the default executable names when the MySQL client tools are on `PATH`. The backup directory defaults to `storage/app/private/backups` and must not be publicly served.

## Create and verify a backup

```powershell
php artisan hardflow:backup
```

Every backup has a neighboring `.json` manifest containing its driver, size, creation time, and SHA-256 checksum. A zero-byte backup is rejected. Backups older than the configured retention period are removed after a successful backup.

The scheduler requests a backup daily at 02:00. Production must run Laravel's scheduler continuously or invoke `php artisan schedule:run` every minute.

## Restore procedure

1. Put the application in maintenance mode: `php artisan down`.
2. Stop queue workers so no writes occur during restoration.
3. List files in the configured private backup directory and select the exact backup filename.
4. Run `php artisan hardflow:restore hardflow-YYYYMMDD-HHMMSS-xxxxxx.sql`.
5. Confirm the destructive prompt. In non-interactive automation, append `--force` only after an operator has approved the exact filename.
6. Run `php artisan migrate:status` and a smoke test.
7. Restart workers and run `php artisan up`.

Before replacement, the restore command creates a new safety backup of the current database. It rejects directory traversal, missing manifests, checksum mismatches, and database-driver mismatches.

## Disaster-recovery drill

At least monthly, restore the newest production backup into an isolated non-production environment, verify authentication and record totals, and record the recovery time. A backup is not considered operationally valid until a restore drill succeeds.
