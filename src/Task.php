<?php

declare(strict_types=1);

namespace Tomloprod\TimeWarden;

use DateTime;
use DateTimeImmutable;
use Tomloprod\TimeWarden\Contracts\Taskable;

final class Task
{
    /**
     * Start time in nanoseconds (hrtime format)
     */
    private int $startTimestamp = 0;

    /**
     * End time in nanoseconds (hrtime format)
     */
    private int $endTimestamp = 0;

    public function __construct(public string $name, private readonly ?Taskable $taskable = null) {}

    public function start(): void
    {
        if (! $this->hasStarted()) {
            /**
             * Force garbage collection before timing starts to ensure accurate
             * measurements. This prevents random GC cycles from affecting
             * benchmark results.
             */
            gc_collect_cycles();

            $this->startTimestamp = hrtime(true);
        }
    }

    public function stop(?callable $fn = null): void
    {
        if (! $this->hasEnded()) {
            $this->endTimestamp = hrtime(true);
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
        // Convertir de nanosegundos a milisegundos
        $duration = ($this->endTimestamp - $this->startTimestamp) / 1_000_000;

        return ($duration > 0) ? round($duration, 2) : 0.0;
    }

    public function getTaskable(): ?Taskable
    {
        return $this->taskable;
    }

    public function hasStarted(): bool
    {
        return $this->startTimestamp !== 0;
    }

    public function hasEnded(): bool
    {
        return $this->endTimestamp !== 0;
    }

    /**
     * Get the start timestamp in nanoseconds
     *
     * @return int Nanoseconds since system boot (hrtime format)
     */
    public function getStartTimestamp(): int
    {
        return $this->startTimestamp;
    }

    /**
     * Get the end timestamp in nanoseconds
     *
     * @return int Nanoseconds since system boot (hrtime format)
     */
    public function getEndTimestamp(): int
    {
        return $this->endTimestamp;
    }

    public function getStartDateTime(): ?DateTimeImmutable
    {
        if ($this->hasStarted()) {
            // Convertir nanosegundos a segundos para DateTime
            $seconds = $this->startTimestamp / 1_000_000_000;

            return new DateTimeImmutable('@'.number_format($seconds, 6, '.', ''));
        }

        return null;
    }

    public function getEndDateTime(): ?DateTimeImmutable
    {
        if ($this->hasEnded()) {
            // Convertir nanosegundos a segundos para DateTime
            $seconds = $this->endTimestamp / 1_000_000_000;

            return new DateTimeImmutable('@'.number_format($seconds, 6, '.', ''));
        }

        return null;
    }

    /**
     * Set start timestamp for testing purposes
     *
     * @param  int  $hrtime  Nanoseconds (hrtime format)
     */
    public function setTestStartTimestamp(int $hrtime): void
    {
        $this->startTimestamp = $hrtime;
    }

    /**
     * Set end timestamp for testing purposes
     *
     * @param  int  $hrtime  Nanoseconds (hrtime format)
     */
    public function setTestEndTimestamp(int $hrtime): void
    {
        $this->endTimestamp = $hrtime;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        /** @var ?DateTimeImmutable $startDateTime */
        $startDateTime = $this->getStartDateTime();

        /** @var ?DateTimeImmutable $endDateTime */
        $endDateTime = $this->getEndDateTime();

        return [
            'name' => $this->name,
            'duration' => $this->getDuration(),
            'friendly_duration' => $this->getFriendlyDuration(),
            'start_timestamp' => $this->startTimestamp, // nanoseconds (hrtime format)
            'end_timestamp' => $this->endTimestamp,     // nanoseconds (hrtime format)
            'start_datetime' => ($startDateTime instanceof DateTimeImmutable) ? $startDateTime->format(DateTime::ATOM) : null,
            'end_datetime' => ($endDateTime instanceof DateTimeImmutable) ? $endDateTime->format(DateTime::ATOM) : null,
        ];
    }
}
