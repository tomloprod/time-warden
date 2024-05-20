<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Group;
use Tomloprod\TimeWarden\Task;

it('can be created with a name', function (): void {
    $group = new Group('GroupName');

    expect($group->name)->toBe('GroupName');
});

it('can add a task', function (): void {
    $group = new Group('GroupName');
    $task = $group->createTask('TaskName');

    expect($group->getTasks())->toContain($task);

    expect($task->getTaskable())->toBe($group);
});

it('can replace the last task', function (): void {
    $group = new Group('GroupName');

    $task1 = $group->createTask('TaskName1');
    $task2 = new Task('TaskName2', $group);

    $group->replaceLastTask($task2);

    expect($group->getTasks())->not->toContain($task1);

    expect($group->getTasks())->toContain($task2);
});

it('can start the last task if it exists', function (): void {
    $group = new Group('GroupName');
    $task = $group->createTask('TaskName');

    $group->start();

    expect($task->hasStarted())->toBeTrue();
});

it('does not start any task if no tasks exist', function (): void {
    $group = new Group('GroupName');

    $group->start();

    expect($group->getLastTask())->toBeNull();
});
