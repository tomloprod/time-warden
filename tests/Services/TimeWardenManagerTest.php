<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Group;
use Tomloprod\TimeWarden\Services\TimeWardenManager;
use Tomloprod\TimeWarden\Task;

beforeEach(function (): void {
    TimeWardenManager::instance()->reset();
});

it('throws exception on clone', function (): void {
    $instance = TimeWardenManager::instance();

    $closure = fn (): mixed => clone $instance;

    expect($closure)->toThrow(Exception::class, 'Cannot clone singleton');
});

it('throws exception on unserialize', function (): void {
    $instance = TimeWardenManager::instance();

    $closure = fn (): mixed => unserialize(serialize($instance));

    expect($closure)->toThrow(Exception::class, 'Cannot unserialize singleton');
});

it('returns the same instance', function (): void {
    $instance1 = TimeWardenManager::instance();
    $instance2 = TimeWardenManager::instance();

    expect($instance1)->toBe($instance2);
});

it('resets the singleton instance', function (): void {
    $instance1 = TimeWardenManager::instance();
    $instance1->group('Group1');

    $instance1->reset();

    $instance2 = TimeWardenManager::instance();
    $groups = $instance2->getGroups();

    expect($groups)
        ->toBeEmpty()
        ->not->toBe($instance1);
});

it('can create and retrieve groups', function (): void {
    $instance = TimeWardenManager::instance();

    $instance->group('Group1')->task('foo');
    $instance->group('Group2')->task('bar');

    $groups = $instance->getGroups();

    expect($groups)->toHaveCount(2);

    expect($groups[0])->toBeInstanceOf(Group::class);
    expect($groups[1])->toBeInstanceOf(Group::class);

    expect($groups[0]->name)->toBe('Group1');
    expect($groups[1]->name)->toBe('Group2');
});

it('overwrite last group if doesn\'t have tasks when a new group is created', function (): void {
    $instance = TimeWardenManager::instance();

    $instance->group('Group1');
    $instance->group('Group2');
    $instance->group('Group3');

    $groups = $instance->getGroups();

    expect($groups)->toHaveCount(1);
    expect($groups[0]->name)->toBe('Group3');
    expect($groups[0])->toBeInstanceOf(Group::class);
});

it('can create tasks of timewarden instance', function (): void {
    $instance = TimeWardenManager::instance();

    $instance->task('Task1');

    $tasks = $instance->getTasks();

    expect($tasks)->toHaveCount(1);

    expect($tasks[0])->toBeInstanceOf(Task::class);
});

it('can create tasks inside group', function (): void {
    $instance = TimeWardenManager::instance();

    $instance->group('Group1')->task('Task1');

    $tasks = $instance->getGroups()[0]->getTasks();

    $timewardenTasks = $instance->getTasks();

    expect($tasks)->toHaveCount(1);

    expect($tasks[0])->toBeInstanceOf(Task::class);

    expect($timewardenTasks)->toHaveCount(0);
});

it('overwrite last task if was never started when a new task is created', function (): void {
    $instance = TimeWardenManager::instance();

    $instance->task('Task1')->task('Task2');

    $tasks = $instance->getTasks();

    expect($tasks)->toHaveCount(1);

    expect($tasks[0]->name)->toBe('Task2');

    expect($tasks[0])->toBeInstanceOf(Task::class);
});

it('stop last task if was never ended when a new task is created', function (): void {
    $instance = TimeWardenManager::instance();

    $instance->task('Task1')->start()->task('Task2');

    $tasks = $instance->getTasks();

    expect($tasks)->toHaveCount(2);

    // Task 1
    expect($tasks[0]->name)->toBe('Task1');

    expect($tasks[0]->hasStarted())->toBeTrue();

    expect($tasks[0]->hasEnded())->toBeTrue();

    expect($tasks[0])->toBeInstanceOf(Task::class);

    // Task 2
    expect($tasks[1]->name)->toBe('Task2');

    expect($tasks[1]->hasStarted())->toBeFalse();

    expect($tasks[1]->hasEnded())->toBeFalse();

    expect($tasks[1])->toBeInstanceOf(Task::class);

    $instance->start();

    expect($tasks[1]->hasStarted())->toBeTrue();

    expect($tasks[1]->hasEnded())->toBeFalse();

    $instance->stop();

    expect($tasks[1]->hasStarted())->toBeTrue();

    expect($tasks[1]->hasEnded())->toBeTrue();
});

test('output returns tasks and groups', function (): void {
    timeWarden()->task('Task 1')->start();

    timeWarden()->task('Task 2')->start();

    timeWarden()->stop();

    timeWarden()->group('Group 1')->task('G1 - Task 1')->start();

    timeWarden()->task('G1 - Task 2')->start();

    timeWarden()->task('G1 - Task 3')->start();

    timeWarden()->group('Group 2')->task('G2 - Task 1')->start();

    $output = timeWarden()->output();

    expect($output)
        ->toBeString()
        ->toContain('default')
        ->toContain('Task 1')
        ->toContain('Task 2')
        ->toContain('G1 - Task 1')
        ->toContain('G1 - Task 2')
        ->toContain('G1 - Task 3')
        ->toContain('Group 1')
        ->toContain('Group 2')
        ->toContain('G2 - Task 1');
});
