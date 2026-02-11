<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Console\Traits;

trait ManagesSignals
{
    /**
     * Set up signal handlers for graceful shutdown.
     */
    protected function setupSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        $handler = function (int $signal) {
            $this->warn("\nReceived termination signal ({$signal}), performing cleanup...");
            $this->performCleanup();
            exit(0);
        };

        pcntl_signal(SIGINT, $handler);
        pcntl_signal(SIGTERM, $handler);
    }

    /**
     * Perform cleanup (must be defined in the using class).
     */
    abstract protected function performCleanup(): void;
}
