<?php

declare(strict_types=1);

namespace ArtemYurov\DbSync\Enums;

enum SyncMode: string
{
    case Refresh = 'refresh';
    case Incremental = 'incremental';
}
