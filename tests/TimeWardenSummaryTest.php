<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\TimeWardenSummary;

it('can obtain an array/json', function (): void {
    timeWarden()->reset();

    timeWarden()->task('Generic Task')->start()->stop();

    $task = timeWarden()->getTasks()[0];
    $task->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $task->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));

    timeWarden()->group('Group1')->task('TaskName1')->start()->stop();

    $groupTask = timeWarden()->getGroups()[0]->getTasks()[0];
    $groupTask->setTestStartTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0000000')));
    $groupTask->setTestEndTimestamp(dateTimeToTimestamp(new DateTimeImmutable('2017-06-05 12:00:00.0320000')));

    /** @var TimeWardenSummary $summary */
    $summary = timeWarden()->getSummary();

    $summaryArray = [
        [
            'name' => 'default',
            'duration' => 0.0,
            'tasks' => [
                [
                    'name' => 'Generic Task',
                    'duration' => 0.0,
                    'friendly_duration' => '0ms',
                    'start_timestamp' => 1496664000.0,
                    'end_timestamp' => 1496664000.0,
                    'start_datetime' => '2017-06-05T12:00:00+00:00',
                    'end_datetime' => '2017-06-05T12:00:00+00:00',
                ],
            ],
        ],
        [
            'name' => 'Group1',
            'duration' => 32.0,
            'tasks' => [
                [
                    'name' => 'TaskName1',
                    'duration' => 32.0,
                    'friendly_duration' => '32ms',
                    'start_timestamp' => 1496664000.0,
                    'end_timestamp' => 1496664000.032,
                    'start_datetime' => '2017-06-05T12:00:00+00:00',
                    'end_datetime' => '2017-06-05T12:00:00+00:00',
                ],
            ],
        ],
    ];

    expect($summary->toArray())->toBe($summaryArray);

    expect($summary->toJson())->toBe(json_encode($summaryArray));
});
