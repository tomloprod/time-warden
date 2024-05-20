<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Services\TimeWardenManager;

if (! function_exists('timeWarden')) {
    function timeWarden(): TimeWardenManager
    {
        return TimeWardenManager::instance();
    }
}

if (! function_exists('timewarden')) {
    function timewarden(): TimeWardenManager
    {
        return TimeWardenManager::instance();
    }
}
