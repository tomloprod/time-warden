<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Concerns\HasTasks;
use Tomloprod\TimeWarden\Contracts\Taskable;

beforeEach(function (): void {
    $this->tasksClass = new class implements Taskable
    {
        use HasTasks;

        public string $name = 'default';
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

it('can obtain an array/json', function (): void {
    $task1 = $this->tasksClass->createTask('TaskName1');
    $task1->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task1->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0190000')));

    $task2 = $this->tasksClass->createTask('TaskName2');
    $task2->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task2->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0230000')));

    $summaryArray = [
        'name' => 'default',
        'duration' => 42.0,
        'tasks' => [
            [
                'name' => 'TaskName1',
                'duration' => 19.0,
                'friendly_duration' => '19ms',
                'start_timestamp' => 1496664000.0,
                'end_timestamp' => 1496664000.019,
                'start_datetime' => '2017-06-05T12:00:00+00:00',
                'end_datetime' => '2017-06-05T12:00:00+00:00',
            ],
            [
                'name' => 'TaskName2',
                'duration' => 23.0,
                'friendly_duration' => '23ms',
                'start_timestamp' => 1496664000.0,
                'end_timestamp' => 1496664000.023,
                'start_datetime' => '2017-06-05T12:00:00+00:00',
                'end_datetime' => '2017-06-05T12:00:00+00:00',
            ],
        ],
    ];

    expect($this->tasksClass->toArray())->toBe($summaryArray);
    expect($this->tasksClass->toJson())->toBe(json_encode($summaryArray));
});
