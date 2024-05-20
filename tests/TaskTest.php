<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Group;
use Tomloprod\TimeWarden\Task;

it('can be created with a name', function (): void {
    $task = new Task('TaskName');

    expect($task->name)->toBe('TaskName');
});

it('starts and stops task', function (): void {
    $task = new Task('TaskName');

    expect($task->hasStarted())->toBeFalse();
    expect($task->hasEnded())->toBeFalse();

    $task->start();

    expect($task->hasStarted())->toBeTrue();
    expect($task->hasEnded())->toBeFalse();

    $task->stop();

    expect($task->hasStarted())->toBeTrue();
    expect($task->hasEnded())->toBeTrue();
});

it('stop task with callable when does not exceed execution time', function (): void {
    $task = new Task('TaskName');
    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0190000')));

    /** @var bool $callableIsExecuted */
    $callableIsExecuted = false;

    $task->stop(static function (Task $task) use (&$callableIsExecuted): void {
        $task->onExceedsMilliseconds(20, static function () use (&$callableIsExecuted): void {
            $callableIsExecuted = true;
        });
    });

    expect($task->getDuration())->toBeLessThan(20);
    expect($callableIsExecuted)->toBeFalse();
});

it('stop task with callable when exceeds execution time', function (): void {
    $task = new Task('TaskName');
    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0210000')));

    /** @var bool $callableIsExecuted */
    $callableIsExecuted = false;

    $task->stop(static function (Task $task) use (&$callableIsExecuted): void {
        $task->onExceedsMilliseconds(20, static function () use (&$callableIsExecuted): void {
            $callableIsExecuted = true;
        });
    });

    expect($task->getDuration())->toBe((float) 21);
    expect($callableIsExecuted)->toBeTrue();
});

it('calculates duration correctly (without usleep)', function (): void {
    $task = new Task('TaskName');
    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.020000')));

    expect($task->getDuration())
        ->toBeGreaterThanOrEqual(20)
        ->toBeLessThanOrEqual(21);
});

it('calculates duration correctly (with usleep)', function (): void {
    $task = new Task('TaskName');
    $task->start();

    // Sleep 7ms.
    usleep(7 * 1000);

    $task->stop();

    expect($task->getDuration())
        ->toBeGreaterThanOrEqual(7)
        ->toBeLessThanOrEqual(8);
})->onlyOnLinux();

it('returns duration as 0 if not started or not stopped', function (): void {
    $task = new Task('TaskName');
    $duration = $task->getDuration();

    expect($duration)->toBe(0.0);
});

test('getFriendlyDuration', function (): void {
    $task = new Task('Task');

    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 13:10:20.030000')));

    expect($task->getFriendlyDuration())->toContain('1h 10min 20sec 30ms');
});

function dateTimeToTimestamp(DateTimeImmutable $datetime): float
{
    return (float) $datetime->getTimestamp() + ((int) $datetime->format('u') / 1000000);
}

test('onExceedsMilliseconds (exceeds test)', function (): void {
    $task = new Task('Task');
    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.1000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.102000')));

    /** @var bool $timeExceeds */
    $timeExceeds = false;

    $task->onExceedsMilliseconds(1, static function () use (&$timeExceeds): void {
        $timeExceeds = true;
    });

    expect($timeExceeds)->toBeTrue();
});

test('onExceedsMilliseconds (does not exceeds test)', function (): void {
    $task2 = new Task('Task');
    $task2->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.1000000')));
    $task2->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.101000')));

    /** @var bool $timeExceeds */
    $timeExceeds = false;

    $task2->onExceedsMilliseconds(1, static function () use (&$timeExceeds): void {
        $timeExceeds = true;
    });

    expect($timeExceeds)->toBeFalse();
});

test('onExceedsSeconds (exceeds test)', function (): void {
    $task = new Task('Task');
    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:10.0000000')));

    /** @var bool $timeExceeds */
    $timeExceeds = false;

    $task->onExceedsSeconds(9, static function () use (&$timeExceeds): void {
        $timeExceeds = true;
    });

    expect($timeExceeds)->toBeTrue();
});

test('onExceedsSeconds (does not exceeds test)', function (): void {
    $task2 = new Task('Task');
    $task2->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task2->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:10.0000000')));

    /** @var bool $timeExceeds */
    $timeExceeds = false;

    $task2->onExceedsSeconds(10, static function () use (&$timeExceeds): void {
        $timeExceeds = true;
    });

    expect($timeExceeds)->toBeFalse();
});

test('onExceedsMinutes (exceeds test)', function (): void {
    $task = new Task('Task 1 exceeds execution time');
    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:10:00.0000000')));

    /** @var bool $timeExceeds */
    $timeExceeds = false;

    $task->onExceedsMinutes(9, static function () use (&$timeExceeds): void {
        $timeExceeds = true;
    });

    expect($timeExceeds)->toBeTrue();
});

test('onExceedsMinutes (does not exceeds test)', function (): void {
    $task2 = new Task('Task');
    $task2->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task2->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:10:00.0000000')));

    /** @var bool $timeExceeds */
    $timeExceeds = false;

    $task2->onExceedsMinutes(10, static function () use (&$timeExceeds): void {
        $timeExceeds = true;
    });

    expect($timeExceeds)->toBeFalse();
});

test('onExceedsHours (exceeds test)', function (): void {
    $task = new Task('Task');
    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 14:01:15.000000')));

    /** @var bool $timeExceeds */
    $timeExceeds = false;

    $task->onExceedsHours(2, static function () use (&$timeExceeds): void {
        $timeExceeds = true;
    });

    expect($timeExceeds)->toBeTrue();
});

test('onExceedsHours (does not exceeds test)', function (): void {
    $task2 = new Task('Task');
    $task2->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.000000')));
    $task2->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:01:15.000000')));

    /** @var bool $timeExceeds */
    $timeExceeds = false;

    $task2->onExceedsHours(2, static function () use (&$timeExceeds): void {
        $timeExceeds = true;
    });

    expect($timeExceeds)->toBeFalse();
});

test('getTaskable', function (): void {
    $group = new Group('GroupName');
    $task = new Task('TaskName', $group);

    expect($task->getTaskable())->toBe($group);
});

test('getters start and end timestamp', function (): void {
    $task = new Task('Task getStartTimestamp');

    $startTimestamp = dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.000000'));
    $endTimestamp = dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.000000'));

    $task->setTestStartTimestamp($startTimestamp);
    $task->setTestEndTimestamp($endTimestamp);

    expect($task->getStartTimestamp())->toBe($startTimestamp);

    expect($task->getEndTimestamp())->toBe($endTimestamp);
});

test('getStartDateTime return DateTime on started tasks', function (): void {
    $task = new Task('Task getStartDateTime -> DateTime');

    $date = '2017-06-05 12:00:00.000000';

    $startTimestamp = dateTimeToTimestamp(new DateTimeImmutable($date));

    $task->setTestStartTimestamp($startTimestamp);

    $startDateTime = $task->getStartDateTime();

    expect($startDateTime)->toBeInstanceOf(DateTimeImmutable::class);

    expect($startDateTime->format('Y-m-d H:i:s.u'))->toBe($date);
});

test('getStartDateTime return null on non started tasks', function (): void {
    $task = new Task('Task getStartDateTime -> null');

    $endDateTime = $task->getStartDateTime();

    expect($endDateTime)->toBeNull();
});

test('getEndDateTime returns DateTime on ended tasks', function (): void {
    $task = new Task('Task getEndDateTime -> DateTime');

    $date = '2017-06-05 12:00:00.000000';

    $timestamp = dateTimeToTimestamp(new DateTimeImmutable($date));

    $task->setTestStartTimestamp($timestamp);
    $task->setTestEndTimestamp($timestamp);

    $endDateTime = $task->getEndDateTime();

    expect($endDateTime)->toBeInstanceOf(DateTimeImmutable::class);

    expect($endDateTime->format('Y-m-d H:i:s.u'))->toBe($date);
});

test('getEndDateTime returns null on non started tasks', function (): void {
    $task = new Task('Task getEndDateTime -> null');

    $endDateTime = $task->getEndDateTime();

    expect($endDateTime)->toBeNull();
});
