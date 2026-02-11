<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Tests\Unit;

use ArtemYurov\DbSync\Contracts\DatabaseAdapterInterface;
use ArtemYurov\DbSync\Services\DependencyGraph;
use Illuminate\Database\Connection;
use PHPUnit\Framework\TestCase;

class DependencyGraphTest extends TestCase
{
    protected DependencyGraph $graph;
    protected DatabaseAdapterInterface $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter = $this->createMock(DatabaseAdapterInterface::class);
        $this->graph = new DependencyGraph($this->adapter);
    }

    public function test_sort_parents_first(): void
    {
        // Configure adapter mock
        $this->adapter->method('getForeignKeyDependencies')
            ->willReturn([
                'orders' => [
                    'depends_on' => ['users', 'products'],
                    'referenced_by' => ['order_items'],
                ],
                'users' => [
                    'depends_on' => [],
                    'referenced_by' => ['orders', 'reviews'],
                ],
                'products' => [
                    'depends_on' => ['categories'],
                    'referenced_by' => ['orders', 'order_items'],
                ],
                'categories' => [
                    'depends_on' => [],
                    'referenced_by' => ['products'],
                ],
                'order_items' => [
                    'depends_on' => ['orders', 'products'],
                    'referenced_by' => [],
                ],
                'reviews' => [
                    'depends_on' => ['users'],
                    'referenced_by' => [],
                ],
            ]);

        $connection = $this->createMock(Connection::class);
        $this->graph->build($connection);

        $tables = ['order_items', 'orders', 'users', 'products', 'categories', 'reviews'];
        $sorted = $this->graph->sortByDependencies($tables, 'parents_first');

        // Verify that parents come before children
        $this->assertLessThan(
            array_search('orders', $sorted),
            array_search('users', $sorted),
            'users should come before orders'
        );
        $this->assertLessThan(
            array_search('products', $sorted),
            array_search('categories', $sorted),
            'categories should come before products'
        );
        $this->assertLessThan(
            array_search('order_items', $sorted),
            array_search('orders', $sorted),
            'orders should come before order_items'
        );
    }

    public function test_sort_children_first(): void
    {
        $this->adapter->method('getForeignKeyDependencies')
            ->willReturn([
                'orders' => [
                    'depends_on' => ['users'],
                    'referenced_by' => ['order_items'],
                ],
                'users' => [
                    'depends_on' => [],
                    'referenced_by' => ['orders'],
                ],
                'order_items' => [
                    'depends_on' => ['orders'],
                    'referenced_by' => [],
                ],
            ]);

        $connection = $this->createMock(Connection::class);
        $this->graph->build($connection);

        $tables = ['users', 'orders', 'order_items'];
        $sorted = $this->graph->sortByDependencies($tables, 'children_first');

        // Verify that children come before parents
        $this->assertLessThan(
            array_search('orders', $sorted),
            array_search('order_items', $sorted),
            'order_items should come before orders'
        );
        $this->assertLessThan(
            array_search('users', $sorted),
            array_search('orders', $sorted),
            'orders should come before users'
        );
    }

    public function test_sort_handles_self_referencing_tables(): void
    {
        $this->adapter->method('getForeignKeyDependencies')
            ->willReturn([
                'categories' => [
                    'depends_on' => ['categories'],
                    'referenced_by' => ['categories', 'products'],
                ],
                'products' => [
                    'depends_on' => ['categories'],
                    'referenced_by' => [],
                ],
            ]);

        $connection = $this->createMock(Connection::class);
        $this->graph->build($connection);

        $tables = ['products', 'categories'];
        $sorted = $this->graph->sortByDependencies($tables, 'parents_first');

        // Should not loop infinitely, categories before products
        $this->assertCount(2, $sorted);
        $this->assertLessThan(
            array_search('products', $sorted),
            array_search('categories', $sorted),
        );
    }

    public function test_sort_handles_empty_tables(): void
    {
        $this->adapter->method('getForeignKeyDependencies')
            ->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $this->graph->build($connection);

        $sorted = $this->graph->sortByDependencies([], 'parents_first');
        $this->assertEmpty($sorted);
    }

    public function test_sort_handles_tables_not_in_graph(): void
    {
        $this->adapter->method('getForeignKeyDependencies')
            ->willReturn([
                'users' => ['depends_on' => [], 'referenced_by' => []],
            ]);

        $connection = $this->createMock(Connection::class);
        $this->graph->build($connection);

        // standalone_table has no FK, but is present in the array
        $tables = ['users', 'standalone_table'];
        $sorted = $this->graph->sortByDependencies($tables, 'parents_first');

        $this->assertContains('users', $sorted);
        $this->assertContains('standalone_table', $sorted);
    }

    public function test_reset_clears_cache(): void
    {
        $this->adapter->method('getForeignKeyDependencies')
            ->willReturn(['users' => ['depends_on' => [], 'referenced_by' => []]]);

        $connection = $this->createMock(Connection::class);
        $this->graph->build($connection);
        $this->assertNotNull($this->graph->getGraph());

        $this->graph->reset();
        $this->assertNull($this->graph->getGraph());
    }
}
