<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden;

use DateTimeImmutable;
use Tomloprod\TimeWarden\Contracts\Taskable;

final class Task
{
    private float $startTimestamp = 0.0;

    private float $endTimestamp = 0.0;

    public function __construct(public string $name, private readonly ?Taskable $taskable = null) {}

    public function start(): void
    {
        if (! $this->hasStarted()) {
            $this->startTimestamp = (float) microtime(true);
        }
    }

    public function stop(?callable $fn = null): void
    {
        if (! $this->hasEnded()) {
            $this->endTimestamp = (float) microtime(true);
        }

        if ($fn !== null) {
            $fn($this);
        }
    }

    public function onExceedsMilliseconds(float $milliseconds, callable $fn): ?self
    {
        $this->stop();

        if ($this->getDuration() > $milliseconds) {
            $fn($this);
        }

        return $this;
    }

    public function onExceedsSeconds(float $seconds, callable $fn): ?self
    {
        $this->stop();

        $durationSeconds = $this->getDuration() / 1000;
        if ($durationSeconds > $seconds) {
            $fn($this);
        }

        return $this;
    }

    public function onExceedsMinutes(float $minutes, callable $fn): ?self
    {
        $this->stop();

        $durationMinutes = $this->getDuration() / 1000 / 60;
        if ($durationMinutes > $minutes) {
            $fn($this);
        }

        return $this;
    }

    public function onExceedsHours(float $hours, callable $fn): ?self
    {
        $this->stop();

        $durationHours = $this->getDuration() / 3600000;
        if ($durationHours > $hours) {
            $fn($this);
        }

        return $this;
    }

    public function getFriendlyDuration(): string
    {
        $durationInMs = $this->getDuration();

        $units = [
            'day' => 24 * 60 * 60 * 1000,
            'h' => 60 * 60 * 1000,
            'min' => 60 * 1000,
            'sec' => 1000,
            'ms' => 1,
        ];

        $timeStrings = [];

        foreach ($units as $name => $divisor) {
            if ($durationInMs >= $divisor) {
                $value = floor($durationInMs / $divisor);
                $durationInMs %= $divisor;
                $timeStrings[] = $value.$name;
            }
        }

        return $timeStrings !== [] ? implode(' ', $timeStrings) : '0ms';
    }

    /**
     * @return float The duration time in milliseconds
     */
    public function getDuration(): float
    {
        $duration = ($this->endTimestamp - $this->startTimestamp) * 1000;

        return ($duration > 0) ? round($duration, 2) : 0.0;
    }

    public function getTaskable(): ?Taskable
    {
        return $this->taskable;
    }

    public function hasStarted(): bool
    {
        return ((int) $this->startTimestamp) !== 0;
    }

    public function hasEnded(): bool
    {
        return ((int) $this->endTimestamp) !== 0;
    }

    public function getStartTimestamp(): float
    {
        return $this->startTimestamp;
    }

    public function getEndTimestamp(): float
    {
        return $this->endTimestamp;
    }

    public function getStartDateTime(): ?DateTimeImmutable
    {
        if ($this->hasStarted()) {
            return new DateTimeImmutable('@'.$this->startTimestamp);
        }

        return null;
    }

    public function getEndDateTime(): ?DateTimeImmutable
    {
        if ($this->hasEnded()) {
            return new DateTimeImmutable('@'.$this->endTimestamp);
        }

        return null;
    }

    public function setTestStartTimestamp(float $microtime): void
    {
        $this->startTimestamp = $microtime;
    }

    public function setTestEndTimestamp(float $microtime): void
    {
        $this->endTimestamp = $microtime;
    }
}
