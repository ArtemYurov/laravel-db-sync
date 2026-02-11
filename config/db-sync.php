<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | The default connection name from the 'connections' array below.
    | Used when the --sync-connection option is not specified.
    |
    */
    'default' => env('DB_SYNC_CONNECTION', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | Default batch size for record read/write operations.
    | Can be overridden per connection or via the --batch-size option.
    |
    */
    'batch_size' => env('DB_SYNC_BATCH_SIZE', 10000),

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'path' => env('DB_SYNC_BACKUP_PATH', storage_path('app/db-sync/backups')),
        'keep_last' => env('DB_SYNC_BACKUP_KEEP_LAST', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Connections
    |--------------------------------------------------------------------------
    |
    | Each connection describes a source (remote DB) -> target (local DB) pair.
    | 'tunnel' is the tunnel name from config/tunnel.php (laravel-autossh-tunnel package).
    |
    | The source connection is automatically registered at runtime
    | and bound to the SSH tunnel.
    |
    */
    'connections' => [

        'production' => [
            // SSH tunnel name from config/tunnel.php
            'tunnel' => env('DB_SYNC_TUNNEL', 'remote_db'),

            // Remote database (source)
            'source' => [
                'driver' => env('DB_SYNC_REMOTE_DRIVER', 'pgsql'),
                'database' => env('DB_SYNC_REMOTE_DATABASE'),
                'username' => env('DB_SYNC_REMOTE_USERNAME'),
                'password' => env('DB_SYNC_REMOTE_PASSWORD'),
            ],

            // Local connection name from config/database.php (target)
            'target' => env('DB_SYNC_TARGET_CONNECTION', 'pgsql'),

            // Tables to exclude from synchronization
            'excluded_tables' => [
                'telescope_entries',
                'telescope_entries_tags',
                'telescope_monitoring',
                'cache',
                'cache_locks',
                'failed_jobs',
                'job_batches',
                'jobs',
                'notifications',
                'sessions',
            ],
        ],

    ],

];
