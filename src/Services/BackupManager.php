<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Services;

use ArtemYurov\DbSync\Contracts\DatabaseAdapterInterface;

class BackupManager
{
    public function __construct(
        protected DatabaseAdapterInterface $adapter,
    ) {}

    /**
     * Create a database backup.
     *
     * @return string Path to the backup file
     */
    public function createBackup(array $connectionConfig, string $backupDir): string
    {
        return $this->adapter->createBackup($connectionConfig, $backupDir);
    }

    /**
     * Restore a database from backup.
     */
    public function restoreBackup(array $connectionConfig, string $backupFile): void
    {
        $this->adapter->restoreBackup($connectionConfig, $backupFile);
    }

    /**
     * Get the list of available backups.
     *
     * @return array<int, array{file: string, filename: string, size: int, size_human: string, date: string, timestamp: int}>
     */
    public function listBackups(string $backupDir): array
    {
        if (!is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . '/*.sql.gz');
        $backups = [];

        foreach ($files as $file) {
            $size = filesize($file);
            $backups[] = [
                'file' => $file,
                'filename' => basename($file),
                'size' => $size,
                'size_human' => $this->formatBytes($size),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'timestamp' => filemtime($file),
            ];
        }

        // Newest first
        usort($backups, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Find a backup by filename (full or partial match).
     */
    public function findBackup(string $name, string $backupDir): ?array
    {
        $backups = $this->listBackups($backupDir);

        foreach ($backups as $backup) {
            if ($backup['filename'] === $name || $backup['file'] === $name) {
                return $backup;
            }
        }

        // Partial match
        foreach ($backups as $backup) {
            if (str_contains($backup['filename'], $name)) {
                return $backup;
            }
        }

        return null;
    }

    /**
     * Delete old backups, keeping the last N.
     */
    public function cleanupOldBackups(string $backupDir, int $keepLast = 5): int
    {
        $backups = $this->listBackups($backupDir);

        if (count($backups) <= $keepLast) {
            return 0;
        }

        $toDelete = array_slice($backups, $keepLast);
        $deleted = 0;

        foreach ($toDelete as $backup) {
            if (unlink($backup['file'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
