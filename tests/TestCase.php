<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Tests;

use ArtemYurov\DbSync\DbSyncServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            DbSyncServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('db-sync.default', 'test');
        $app['config']->set('db-sync.connections.test', [
            'tunnel' => 'test_tunnel',
            'source' => [
                'driver' => 'pgsql',
                'database' => 'test_remote',
                'username' => 'test',
                'password' => 'test',
            ],
            'target' => 'pgsql',
            'excluded_tables' => ['sessions', 'cache'],
        ]);
    }
}
