<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Services\TimeWardenManager;
use Tomloprod\TimeWarden\Support\Facades\TimeWarden;
use Tomloprod\TimeWarden\Task;

beforeEach(function (): void {
    TimeWarden::reset();
});

test('facade returns the same instance', function (): void {
    $instance1 = TimeWardenManager::instance();
    $instance2 = TimeWarden::instance();

    expect($instance1)->toBe($instance2);
});

it('can create tasks using TimeWarden facade', function (): void {
    TimeWarden::task('Task1');

    $tasks = TimeWarden::instance()->getTasks();

    expect($tasks)->toHaveCount(1);

    expect($tasks[0])->toBeInstanceOf(Task::class);
});
