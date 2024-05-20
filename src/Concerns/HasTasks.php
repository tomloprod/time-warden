<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden\Concerns;

use Tomloprod\TimeWarden\Task;

trait HasTasks
{
    /**
     * @var array<Task>
     */
    private array $tasks = [];

    public function createTask(string $taskName): Task
    {
        $task = new Task($taskName, $this);

        $this->tasks[] = $task;

        return $task;
    }

    /**
     * @return array<Task>
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return float The duration time in milliseconds
     */
    public function getDuration(): float
    {
        $duration = 0.0;

        /** @var Task $task */
        foreach ($this->getTasks() as $task) {
            $duration += $task->getDuration();
        }

        return ($duration > 0) ? round($duration, 2) : 0.0;
    }

    public function getLastTask(): ?Task
    {
        /** @var Task|bool $lastTask */
        $lastTask = end($this->tasks);

        return ($lastTask instanceof Task) ? $lastTask : null;
    }
}
