<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync;

use ArtemYurov\DbSync\Console\RefreshCommand;
use ArtemYurov\DbSync\Console\RestoreCommand;
use ArtemYurov\DbSync\Console\SyncCommand;
use Illuminate\Support\ServiceProvider;

class DbSyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/db-sync.php',
            'db-sync'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/db-sync.php' => config_path('db-sync.php'),
            ], 'db-sync-config');

            $this->commands([
                RefreshCommand::class,
                SyncCommand::class,
                RestoreCommand::class,
            ]);
        }
    }
}
