<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden;

final class TimeWardenSummary
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var array<string, mixed> $tasksInfo */
        $tasksInfo = [];

        if (timeWarden()->getTasks() !== []) {
            $tasksInfo[] = timeWarden()->toArray();
        }

        /** @var Group $group */
        foreach (timeWarden()->getGroups() as $group) {
            $tasksInfo[] = $group->toArray();
        }

        return $tasksInfo;
    }

    public function toJson(): string
    {
        $json = json_encode($this->toArray());

        return ($json === false) ? '[]' : $json;
    }
}
