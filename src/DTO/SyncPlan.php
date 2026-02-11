<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\DTO;

/**
 * Synchronization plan.
 */
final class SyncPlan
{
    /** @var TableDiff[] */
    public array $tablesToSync = [];

    /** @var string[] Tables to recreate (DROP+CREATE) */
    public array $tablesToRefresh = [];

    /** @var string[] Views to refresh */
    public array $viewsToRefresh = [];

    /** @var string[] Missing tables */
    public array $missingTables = [];

    /** @var string[] Tables with changed structure */
    public array $changedTables = [];

    /** @var string[] Missing views */
    public array $missingViews = [];

    /** @var string[] Views with changed structure */
    public array $changedViews = [];

    public function hasChanges(): bool
    {
        return !empty($this->tablesToSync)
            || !empty($this->tablesToRefresh)
            || !empty($this->viewsToRefresh);
    }

    public function hasStructuralChanges(): bool
    {
        return !empty($this->tablesToRefresh)
            || !empty($this->viewsToRefresh)
            || !empty($this->missingTables)
            || !empty($this->changedTables)
            || !empty($this->missingViews)
            || !empty($this->changedViews);
    }
}
