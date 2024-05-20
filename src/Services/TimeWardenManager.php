<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden\Services;

use Exception;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\BufferedOutput;
use Tomloprod\TimeWarden\Concerns\HasTasks;
use Tomloprod\TimeWarden\Contracts\Taskable;
use Tomloprod\TimeWarden\Group;
use Tomloprod\TimeWarden\Task;

final class TimeWardenManager implements Taskable
{
    use HasTasks;

    private static TimeWardenManager $instance;

    /**
     * @var array<Group>
     */
    private array $groups = [];

    private function __construct()
    {
    }

    public function __clone()
    {
        throw new Exception('Cannot clone singleton');
    }

    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }

    /**
     * Get the singleton instance of TimeWarden.
     */
    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function reset(): self
    {
        self::$instance = new self();

        return self::$instance;
    }

    public function group(string $groupName): self
    {
        $this->stop();

        /** @todo do the same as task(). overwrite empty groups, avoid groups with same name (in this case... active group with name?) */
        $this->groups[] = new Group($groupName);

        return self::$instance;
    }

    public function task(string $taskName): self
    {
        /** @var Taskable $taskable */
        $taskable = $this->getActiveTaskable();

        /** @var Task|null $lastTask */
        $lastTask = $taskable->getLastTask();

        // If the last task was never started, we replace its name with `$taskName`
        if ($lastTask instanceof Task && ! $lastTask->hasStarted()) {
            $lastTask->name = $taskName;
        } else {
            // If there is a task, but it has already started, we stop it
            if ($lastTask instanceof Task && $lastTask->hasStarted()) {
                $lastTask->stop();
            }

            // And add the task to the taskable.
            $taskable->createTask($taskName);
        }

        return self::$instance;
    }

    public function start(): self
    {
        /** @var Task|null $lastTask */
        $lastTask = $this->getActiveTaskable()->getLastTask();

        if ($lastTask instanceof Task) {
            $lastTask->start();
        }

        return self::$instance;
    }

    public function stop(?callable $fn = null): self
    {
        /** @var Task|null $lastTask */
        $lastTask = $this->getActiveTaskable()->getLastTask();

        if ($lastTask instanceof Task) {
            $lastTask->stop($fn);
        }

        return self::$instance;
    }

    /**
     * @return array<Group>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function output(): string
    {
        $this->stop();

        /** @var string $output */
        $output = '';

        /** @var array<string> $columns */
        $columns = [
            'GROUP',
            'TASK',
            'DURATION (MS)',
        ];

        /** @var array<string|float> $rows */
        $rows = [];

        $totalGroups = 0;
        $totalTasks = 0;
        $totalDuration = $this->getDuration();

        /** @var Task $task */
        foreach ($this->getTasks() as $iTask => $task) {
            $rows[] = [
                ($iTask === 0) ? 'default ('.$this->getDuration().' ms)' : '',
                $task->name,
                $task->getDuration(),
            ];

            $totalTasks++;
        }

        if ($totalTasks > 0) {
            $rows[] = new TableSeparator();
        }

        /** @var Group|null $lastIterateGroup */
        $lastIterateGroup = null;

        /** @var Group $group */
        foreach ($this->groups as $iGroup => $group) {

            /** @var Task $task */
            foreach ($group->getTasks() as $task) {
                $rows[] = [
                    ($lastIterateGroup !== $group) ? $group->name.' ('.$group->getDuration().' ms)' : '',
                    $task->name,
                    $task->getDuration(),
                ];

                $lastIterateGroup = $group;
                $totalTasks++;
            }

            if ($iGroup !== count($this->groups) - 1) {
                $rows[] = new TableSeparator();
            }

            $totalDuration += $group->getDuration();
            $totalGroups++;
        }

        // Footer
        //$rows[] = new TableSeparator();
        //$rows[] = ['Nº groups', 'Nº tasks', 'Total duration'];
        //$rows[] = [$totalGroups, $totalTasks, $totalDuration];
        //$rows[] = ['', '', 'Total ' . $totalDuration];

        $output = new BufferedOutput();
        $table = new Table($output);

        $table
            ->setHeaders($columns)
            ->setRows($rows)
            ->setStyle('box-double')
            // ->setFooterTitle('Thanks for using TimeWarden')
            ->setFooterTitle('Total: '.round($totalDuration, 2).' ms')

            ->setHeaderTitle('TIMEWARDEN');

        $table->render();

        $output = $output->fetch();

        return "\n".$output;
    }

    private function getActiveTaskable(): Taskable
    {
        return $this->getLastGroup() ?? $this;
    }

    private function getLastGroup(): ?Group
    {
        /** @var Group|bool $lastGroup */
        $lastGroup = end($this->groups);

        return ($lastGroup instanceof Group) ? $lastGroup : null;
    }
}
