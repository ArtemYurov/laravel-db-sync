<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\DTO;

/**
 * Connection configuration for synchronization.
 */
final readonly class SyncConfig
{
    public function __construct(
        public string $name,
        public string $tunnel,
        public array $source,
        public string $target,
        public array $excludedTables = [],
        public int $batchSize = 1000,
        public string $backupPath = '',
        public int $keepLastBackups = 5,
    ) {}

    /**
     * Create from a configuration array.
     */
    public static function fromArray(string $name, array $config): self
    {
        $backup = config('db-sync.backup', []);

        return new self(
            name: $name,
            tunnel: $config['tunnel'],
            source: $config['source'],
            target: $config['target'],
            excludedTables: $config['excluded_tables'] ?? [],
            batchSize: $config['batch_size'] ?? config('db-sync.batch_size', 1000),
            backupPath: $backup['path'] ?? storage_path('app/db-sync/backups'),
            keepLastBackups: $backup['keep_last'] ?? 5,
        );
    }

    /**
     * Get the source database connection config (for Laravel DB registration).
     */
    public function getSourceDatabaseConfig(): array
    {
        return array_merge([
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ], $this->source);
    }

    /**
     * Get the target database connection config from database.php.
     */
    public function getTargetDatabaseConfig(): array
    {
        return config("database.connections.{$this->target}", []);
    }

    /**
     * Get the database driver.
     */
    public function getDriver(): string
    {
        return $this->source['driver'] ?? config("database.connections.{$this->target}.driver", 'pgsql');
    }
}
