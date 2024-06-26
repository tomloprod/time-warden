<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Group;

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
