<?php

namespace App\Enums;

enum SyncStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
