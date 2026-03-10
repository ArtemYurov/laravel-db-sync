<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Tests\Unit;

use ArtemYurov\DbSync\Console\BaseDbSyncCommand;
use ArtemYurov\DbSync\Tests\TestCase;

class FilterExcludedTablesTest extends TestCase
{
    private BaseDbSyncCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous subclass to access the protected method
        $this->command = new class extends BaseDbSyncCommand {
            protected $signature = 'test:filter-excluded';
            protected $description = 'Test command';

            public function handle(): int
            {
                return self::SUCCESS;
            }

            // Expose protected method for testing
            public function callFilterExcludedTables(array $tableNames, array $excludedTables, array $requestedTables = []): array
            {
                return $this->filterExcludedTables($tableNames, $excludedTables, $requestedTables);
            }
        };
    }

    public function test_excluded_tables_are_filtered_out(): void
    {
        $result = $this->command->callFilterExcludedTables(
            ['users', 'sessions', 'cache', 'orders'],
            ['sessions', 'cache'],
        );

        $this->assertEquals(['users', 'orders'], $result);
    }

    public function test_explicitly_requested_table_overrides_exclusion(): void
    {
        $result = $this->command->callFilterExcludedTables(
            ['users', 'sessions', 'cache', 'orders'],
            ['sessions', 'cache'],
            ['sessions'], // explicitly requested via --tables
        );

        $this->assertEquals(['users', 'sessions', 'orders'], $result);
    }

    public function test_all_requested_excluded_tables_are_kept(): void
    {
        $result = $this->command->callFilterExcludedTables(
            ['users', 'sessions', 'cache', 'orders'],
            ['sessions', 'cache'],
            ['sessions', 'cache'], // both excluded tables requested
        );

        $this->assertEquals(['users', 'sessions', 'cache', 'orders'], $result);
    }

    public function test_no_exclusion_when_excluded_list_is_empty(): void
    {
        $result = $this->command->callFilterExcludedTables(
            ['users', 'sessions', 'orders'],
            [],
        );

        $this->assertEquals(['users', 'sessions', 'orders'], $result);
    }

    public function test_no_effect_when_requested_tables_not_in_excluded(): void
    {
        $result = $this->command->callFilterExcludedTables(
            ['users', 'sessions', 'cache', 'orders'],
            ['sessions', 'cache'],
            ['users', 'orders'], // requested tables that are not excluded anyway
        );

        $this->assertEquals(['users', 'orders'], $result);
    }

    public function test_returns_reindexed_array(): void
    {
        $result = $this->command->callFilterExcludedTables(
            ['a', 'b', 'c', 'd'],
            ['b'],
        );

        // Verify keys are sequential (0, 1, 2)
        $this->assertSame([0, 1, 2], array_keys($result));
        $this->assertEquals(['a', 'c', 'd'], $result);
    }
}
