<?php

return [
    'backup' => [
        'path' => env('HARDFLOW_BACKUP_PATH', storage_path('app/private/backups')),
        'mysqldump_binary' => env('HARDFLOW_MYSQLDUMP_PATH', 'mysqldump'),
        'mysql_binary' => env('HARDFLOW_MYSQL_PATH', 'mysql'),
        'retention_days' => (int) env('HARDFLOW_BACKUP_RETENTION_DAYS', 30),
    ],
];
