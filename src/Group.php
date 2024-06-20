<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden;

use Tomloprod\TimeWarden\Concerns\HasTasks;
use Tomloprod\TimeWarden\Contracts\Taskable;

final class Group implements Taskable
{
    use HasTasks;

    public function __construct(public string $name) {}

    public function start(): void
    {
        /** @var Task|null $lastTask */
        $lastTask = $this->getLastTask();

        if ($lastTask instanceof Task) {
            $lastTask->start();
        }
    }
}
