<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\DTO;

/**
 * Single table diff (analysis result).
 */
final class TableDiff
{
    public function __construct(
        public readonly string $table,
        public bool $needsSync = false,
        public int $localCount = 0,
        public int $remoteCount = 0,
        public bool $hasUpdates = false,
        public array $idsToDelete = [],
        public bool $metadataError = false,
        public bool $refreshed = false,
        public bool $isParent = false,
    ) {}

    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'needs_sync' => $this->needsSync,
            'local_count' => $this->localCount,
            'remote_count' => $this->remoteCount,
            'has_updates' => $this->hasUpdates,
            'ids_to_delete' => $this->idsToDelete,
            'metadata_error' => $this->metadataError,
            'refreshed' => $this->refreshed,
            'is_parent' => $this->isParent,
        ];
    }

    public static function fromArray(array $data): self
    {
        $diff = new self(table: $data['table']);
        $diff->needsSync = $data['needs_sync'] ?? false;
        $diff->localCount = $data['local_count'] ?? 0;
        $diff->remoteCount = $data['remote_count'] ?? 0;
        $diff->hasUpdates = $data['has_updates'] ?? false;
        $diff->idsToDelete = $data['ids_to_delete'] ?? [];
        $diff->metadataError = $data['metadata_error'] ?? false;
        $diff->refreshed = $data['refreshed'] ?? false;
        $diff->isParent = $data['is_parent'] ?? false;

        return $diff;
    }
}
