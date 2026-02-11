<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Services;

use ArtemYurov\DbSync\Contracts\DatabaseAdapterInterface;
use Illuminate\Database\Connection;

class DependencyGraph
{
    /** Cached dependency graph */
    protected ?array $graph = null;

    public function __construct(
        protected DatabaseAdapterInterface $adapter,
    ) {}

    /**
     * Build the FK dependency graph.
     */
    public function build(Connection $connection): array
    {
        if ($this->graph !== null) {
            return $this->graph;
        }

        $this->graph = $this->adapter->getForeignKeyDependencies($connection);

        return $this->graph;
    }

    /**
     * Reset the graph cache.
     */
    public function reset(): void
    {
        $this->graph = null;
    }

    /**
     * Topological sort of tables by dependencies.
     *
     * @param string[] $tables
     * @param string $order 'parents_first' or 'children_first'
     * @return string[]
     */
    public function sortByDependencies(array $tables, string $order = 'parents_first'): array
    {
        $graph = $this->graph ?? [];
        $sorted = [];
        $visited = [];
        $visiting = [];

        $visit = function (string $table) use (&$visit, &$visited, &$visiting, &$sorted, $graph, $tables, $order) {
            if (!in_array($table, $tables) || isset($visiting[$table]) || isset($visited[$table])) {
                return;
            }

            $visiting[$table] = true;

            if ($order === 'parents_first') {
                foreach ($graph[$table]['depends_on'] ?? [] as $dependency) {
                    $visit($dependency);
                }
            } else {
                foreach ($graph[$table]['referenced_by'] ?? [] as $childTable) {
                    $visit($childTable);
                }
            }

            unset($visiting[$table]);
            $visited[$table] = true;
            $sorted[] = $table;
        };

        foreach ($tables as $table) {
            $visit($table);
        }

        return $sorted;
    }

    /**
     * Get the graph (if built).
     */
    public function getGraph(): ?array
    {
        return $this->graph;
    }
}
