<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Concerns\HasTasks;
use Tomloprod\TimeWarden\Contracts\Taskable;

beforeEach(function (): void {
    $this->tasksClass = new class implements Taskable
    {
        use HasTasks;
    };
});

it('can add a task', function (): void {
    $task = $this->tasksClass->createTask('TaskName');

    expect($this->tasksClass->getTasks())
        ->toContain($task);
});

it('can retrieve the last task', function (): void {
    $task1 = $this->tasksClass->createTask('TaskName1');
    $task2 = $this->tasksClass->createTask('TaskName2');

    expect($this->tasksClass->getLastTask())
        ->toBe($task2);
});

it('returns null when retrieving the last task if there are no tasks', function (): void {
    expect($this->tasksClass->getLastTask())
        ->toBeNull();
});
