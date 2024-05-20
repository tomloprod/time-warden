<?php

declare(strict_types=1);

use Tomloprod\TimeWarden\Services\TimeWardenManager;

test('timeWarden (w uppercase) alias return instance of TimeWarden', function (): void {
    expect(timeWarden())
        ->toBeInstanceOf(TimeWardenManager::class);
});

test('timewarden (w lowercase) alias return instance of TimeWarden', function (): void {
    expect(timewarden())
        ->toBeInstanceOf(TimeWardenManager::class);
});
