<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden\Contracts;

use Tomloprod\TimeWarden\Task;

interface Taskable
{
    public function createTask(string $taskName): Task;

    public function replaceLastTask(Task $task): void;

    /**
     * @return array<Task>
     */
    public function getTasks(): array;

    public function getLastTask(): ?Task;

    public function getDuration(): float;
}
