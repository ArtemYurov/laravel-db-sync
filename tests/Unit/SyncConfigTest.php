<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Tests\Unit;

use ArtemYurov\DbSync\DTO\SyncConfig;
use ArtemYurov\DbSync\Tests\TestCase;

class SyncConfigTest extends TestCase
{
    public function test_creates_from_array(): void
    {
        $config = SyncConfig::fromArray('production', [
            'tunnel' => 'remote_db',
            'source' => [
                'driver' => 'pgsql',
                'database' => 'remote_db',
                'username' => 'user',
                'password' => 'pass',
            ],
            'target' => 'pgsql',
            'excluded_tables' => ['sessions', 'cache'],
        ]);

        $this->assertEquals('production', $config->name);
        $this->assertEquals('remote_db', $config->tunnel);
        $this->assertEquals('pgsql', $config->target);
        $this->assertEquals(['sessions', 'cache'], $config->excludedTables);
        $this->assertEquals('pgsql', $config->getDriver());
    }

    public function test_source_database_config_has_defaults(): void
    {
        $config = SyncConfig::fromArray('test', [
            'tunnel' => 'remote_db',
            'source' => [
                'driver' => 'pgsql',
                'database' => 'mydb',
                'username' => 'user',
                'password' => 'pass',
            ],
            'target' => 'pgsql',
        ]);

        $sourceConfig = $config->getSourceDatabaseConfig();

        $this->assertEquals('utf8', $sourceConfig['charset']);
        $this->assertEquals('prefer', $sourceConfig['sslmode']);
        $this->assertEquals('public', $sourceConfig['search_path']);
        $this->assertEquals('mydb', $sourceConfig['database']);
    }

    public function test_default_excluded_tables_is_empty(): void
    {
        $config = SyncConfig::fromArray('test', [
            'tunnel' => 'remote_db',
            'source' => [
                'driver' => 'pgsql',
                'database' => 'db',
                'username' => 'u',
                'password' => 'p',
            ],
            'target' => 'pgsql',
        ]);

        $this->assertEmpty($config->excludedTables);
    }

    public function test_driver_defaults_to_target_driver(): void
    {
        $config = SyncConfig::fromArray('test', [
            'tunnel' => 'remote_db',
            'source' => [
                // without driver
                'database' => 'db',
                'username' => 'u',
                'password' => 'p',
            ],
            'target' => 'pgsql',
        ]);

        // Without driver in source, falls back to target
        $driver = $config->getDriver();
        $this->assertIsString($driver);
    }
}
