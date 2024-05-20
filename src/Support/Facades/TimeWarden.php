<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden\Support\Facades;

use Tomloprod\TimeWarden\Group;
use Tomloprod\TimeWarden\Services\TimeWardenManager;
use Tomloprod\TimeWarden\Task;

/**
 * @method static TimeWardenManager reset()
 * @method static TimeWardenManager group(string $groupName)
 * @method static TimeWardenManager task(string $taskName)
 * @method static TimeWardenManager start()
 * @method static Task|null stop()
 * @method static array<Group> getGroups()
 * @method static string output()
 *
 * Taskable methods:
 * @method static Task createTask(string $taskName)
 * @method static void replaceLastTask(Task $task)
 * @method static array<Task> getTasks(string $taskName)
 * @method static Task|null getLastTask()
 * @method static float getDuration()
 */
final class TimeWarden
{
    /**
     * @param  array<mixed>  $args
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = TimeWardenManager::instance();

        return $instance->$method(...$args);
    }
}
