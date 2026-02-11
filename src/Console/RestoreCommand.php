<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Console;

use ArtemYurov\DbSync\Adapters\PgsqlAdapter;
use ArtemYurov\DbSync\Contracts\DatabaseAdapterInterface;
use ArtemYurov\DbSync\Exceptions\DbSyncException;
use ArtemYurov\DbSync\Services\BackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Restore local database from backup
 */
class RestoreCommand extends Command
{
    protected $signature = 'db-sync:restore
                            {file? : Backup file name to restore}
                            {--sync-connection= : Connection name from config/db-sync.php}
                            {--force : Run without confirmation}
                            {--list : Only show the list of backups}';

    protected $description = 'Restore local database from backup';

    protected ?DatabaseAdapterInterface $adapter = null;
    protected ?BackupManager $backupManager = null;
    protected string $targetConnection = 'pgsql';
    protected string $backupDir = '';

    public function handle(): int
    {
        try {
            $this->initialize();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $backups = $this->backupManager->listBackups($this->backupDir);

        if (empty($backups)) {
            $this->warn('No backups found in ' . $this->backupDir);
            return self::SUCCESS;
        }

        // If only listing
        if ($this->option('list')) {
            $this->displayBackupList($backups);
            return self::SUCCESS;
        }

        // If file is specified directly
        $file = $this->argument('file');
        if ($file) {
            $backup = $this->backupManager->findBackup($file, $this->backupDir);
            if (!$backup) {
                $this->error("Backup '{$file}' not found");
                $this->displayBackupList($backups);
                return self::FAILURE;
            }
        } else {
            // Interactive selection
            $backup = $this->selectBackup($backups);
            if (!$backup) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        // Confirmation
        if (!$this->confirmRestore($backup)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        // Restore
        return $this->restoreBackup($backup);
    }

    protected function initialize(): void
    {
        $connectionName = $this->option('sync-connection') ?? config('db-sync.default', 'production');
        $connectionConfig = config("db-sync.connections.{$connectionName}");

        if (!$connectionConfig) {
            throw new DbSyncException("db-sync connection configuration '{$connectionName}' not found");
        }

        $this->targetConnection = $connectionConfig['target'] ?? 'pgsql';
        $this->backupDir = config('db-sync.backup.path', storage_path('app/db-sync/backups'));

        $driver = $connectionConfig['source']['driver']
            ?? config("database.connections.{$this->targetConnection}.driver", 'pgsql');

        $this->adapter = match ($driver) {
            'pgsql' => new PgsqlAdapter(),
            default => throw new DbSyncException("Database driver '{$driver}' is not supported"),
        };

        $this->backupManager = new BackupManager($this->adapter);
    }

    protected function displayBackupList(array $backups): void
    {
        $this->info('Available backups:');
        $this->newLine();

        $rows = [];
        foreach ($backups as $index => $backup) {
            $rows[] = [
                $index + 1,
                $backup['filename'],
                $backup['size_human'],
                $backup['date'],
            ];
        }

        $this->table(['#', 'File', 'Size', 'Created at'], $rows);
    }

    protected function selectBackup(array $backups): ?array
    {
        $this->displayBackupList($backups);
        $this->newLine();

        $choices = [];
        foreach ($backups as $index => $backup) {
            $choices[$index + 1] = $backup['filename'] . ' (' . $backup['size_human'] . ', ' . $backup['date'] . ')';
        }
        $choices[0] = 'Cancel';

        $choice = $this->choice('Select a backup to restore', $choices, 0);

        if ($choice === 'Cancel') {
            return null;
        }

        $selectedIndex = array_search($choice, $choices);
        if ($selectedIndex === false || $selectedIndex === 0) {
            return null;
        }

        return $backups[$selectedIndex - 1];
    }

    protected function confirmRestore(array $backup): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $database = config("database.connections.{$this->targetConnection}.database");

        $this->newLine();
        $this->warn('⚠ WARNING: This operation will completely replace all data in database "' . $database . '"!');
        $this->info('   File: ' . $backup['filename']);
        $this->info('   Size: ' . $backup['size_human']);
        $this->info('   Date: ' . $backup['date']);
        $this->newLine();

        return $this->confirm('Continue with restore?', false);
    }

    protected function restoreBackup(array $backup): int
    {
        $config = config("database.connections.{$this->targetConnection}");

        $this->info('Restoring from backup...');
        $this->info('   File: ' . $backup['filename']);
        $this->newLine();

        // Clear schema
        $this->info('   Clearing current database...');
        try {
            $this->adapter->dropSchema(DB::connection($this->targetConnection));
            $this->info('   ✓ Schema cleared');
        } catch (\Exception $e) {
            $this->error('   ✗ Schema clearing error: ' . $e->getMessage());
            return self::FAILURE;
        }

        // Restore
        $this->info('   Restoring data...');
        try {
            $this->backupManager->restoreBackup($config, $backup['file']);
            $this->info('   ✓ Data restored');
        } catch (\Exception $e) {
            $this->warn('   ⚠ ' . $e->getMessage());
        }

        // Verification
        $this->newLine();
        $this->info('Verifying result:');

        try {
            $tables = $this->adapter->getTablesList(DB::connection($this->targetConnection));
            $this->info('   Tables: ' . count($tables));

            $views = $this->adapter->getViewsList(DB::connection($this->targetConnection));
            $this->info('   Views: ' . count($views));
        } catch (\Exception $e) {
            $this->warn('   Could not retrieve statistics: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('✓ Restore completed!');

        return self::SUCCESS;
    }
}
