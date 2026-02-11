<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Console\Traits;

trait ManagesMemoryLimit
{
    /**
     * Set the memory limit.
     *
     * @param int $megabytes Required amount in MB (-1 for unlimited)
     */
    protected function ensureMemoryLimit(int $megabytes = 512): void
    {
        $currentLimit = ini_get('memory_limit');
        $currentBytes = $this->convertToBytes($currentLimit);

        // Already unlimited — no changes needed
        if ($currentBytes === -1) {
            return;
        }

        $targetBytes = $megabytes === -1 ? -1 : $megabytes * 1024 * 1024;

        // Current limit is already sufficient — no changes needed
        if ($targetBytes !== -1 && $currentBytes >= $targetBytes) {
            return;
        }

        $newLimit = $megabytes === -1 ? '-1' : "{$megabytes}M";
        ini_set('memory_limit', $newLimit);

        $actualLimit = ini_get('memory_limit');
        $actualBytes = $this->convertToBytes($actualLimit);

        $isSuccess = $actualBytes === -1 || ($targetBytes !== -1 && $actualBytes >= $targetBytes);
        $displayTarget = $megabytes === -1 ? 'unlimited' : $newLimit;
        $displayActual = $actualBytes === -1 ? 'unlimited' : $actualLimit;

        if ($isSuccess) {
            $this->info("Memory limit: {$currentLimit} -> {$displayActual}");
        } else {
            $this->warn("Failed to set memory limit to {$displayTarget} (current: {$actualLimit})");
        }
    }

    protected function convertToBytes(string $memoryLimit): int|float
    {
        if ('-1' === $memoryLimit) {
            return -1;
        }

        $memoryLimit = strtolower($memoryLimit);
        $max = strtolower(ltrim($memoryLimit, '+'));
        if (str_starts_with($max, '0x')) {
            $max = intval($max, 16);
        } elseif (str_starts_with($max, '0')) {
            $max = intval($max, 8);
        } else {
            $max = (int) $max;
        }

        switch (substr($memoryLimit, -1)) {
            case 't': $max *= 1024;
            // no break
            case 'g': $max *= 1024;
            // no break
            case 'm': $max *= 1024;
            // no break
            case 'k': $max *= 1024;
        }

        return $max;
    }
}
