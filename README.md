<p align="center">
    <p align="center">
        <a href="https://github.com/tomloprod/time-warden/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/tomloprod/time-warden/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/tomloprod/time-warden"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/tomloprod/time-warden"></a>
        <a href="https://packagist.org/packages/tomloprod/time-warden"><img alt="Latest Version" src="https://img.shields.io/packagist/v/tomloprod/time-warden"></a>
        <a href="https://packagist.org/packages/tomloprod/time-warden"><img alt="License" src="https://img.shields.io/packagist/l/tomloprod/time-warden"></a>
    </p>
</p>

------
## ⏱️ **About TimeWarden**

TimeWarden is a lightweight PHP library that allows you to **monitor the processing time of tasks** (*useful during the development stage and debugging*) and also lets you set estimated execution times for tasks, **enabling reactive actions** when tasks exceed their estimated duration.

TimeWarden is framework-agnostic, meaning it's not exclusive to any particular framework. It can seamlessly integrate into any PHP application, whether they utilize frameworks like Laravel (🧡), Symfony, or operate without any framework at all.

## **✨ Getting Started**

### Reactive Actions
You can specify an estimated execution time for each task and set an action to be performed when the time is exceeded (*example: send an email, add an entry to the error log, etc.*).

#### Example
```php
timeWarden()->task('Checking articles')->start();

foreach ($articles as $article) {
    // Perform long process... 🕒 
}

// Using traditional anonymous function
timeWarden()->stop(static function (Task $task): void {
    $task->onExceedsMilliseconds(500, static function (Task $task): void {
        // Do what you need, for example, send an email 🙂
        Mail::to('foo@bar.com')->queue(
            new SlowArticleProcess($task)
        );
    });
});

// Or using an arrow function
timeWarden()->stop(static function (Task $task): void {
    $task->onExceedsMilliseconds(500, fn (Task $task) => Log::error($task->name.' has taken too long'));
});
```

#### Available methods

If you're not convinced about using `onExceedsMilliseconds`, you have other options:
```php
$task->onExceedsSeconds(10, function () { ... });
$task->onExceedsMinutes(5, function () { ... });
$task->onExceedsHours(2, function () { ... });
```

### Execution Time Debugging
It allows you to measure the execution time of tasks in your application, as well as the possibility of adding those tasks to a group.

#### Simple tasks

```php
timeWarden()->task('Articles task');

foreach ($articles as $article) {
    // Perform long process...
}

// Previous task is automatically stopped when a new task is created
timeWarden()->task('Customers task');

foreach ($customers as $customer) {
    // Perform long process...
}

echo timeWarden()->output();
```
**Result:**
```log
╔═════════════════════ TIMEWARDEN ═════╤═══════════════╗
║ GROUP               │ TASK           │ DURATION (MS) ║
╠═════════════════════╪════════════════╪═══════════════╣
║ default (320.37 ms) │ Articles task  │ 70.23         ║
║                     │ Customers task │ 250.14        ║
╚══════════════════ Total: 320.37 ms ══╧═══════════════╝
```

#### Grouped tasks

```php
timeWarden()->group('Articles')->task('Loop of articles')->start();

foreach ($articles as $article) {
    // Perform first operations
}

timeWarden()->task('Other articles process')->start();
Foo::bar();

// Previous task is automatically stopped when a new task is created
timeWarden()->group('Customers')->task('Customers task')->start();

foreach ($customers as $customer) {
    // Perform long process...
}

timeWarden()->task('Other customer process')->start();
Bar::foo();

echo timeWarden()->output();
```
**Result:**
```log
╔═══════════════════════╤══ TIMEWARDEN ══════════╤═══════════════╗
║ GROUP                 │ TASK                   │ DURATION (MS) ║
╠═══════════════════════╪════════════════════════╪═══════════════╣
║ Articles (85.46 ms)   │ Loop of articles       │ 70.24         ║
║                       │ Other articles process │ 15.22         ║
╟───────────────────────┼────────────────────────┼───────────────╢
║ Customers (280.46 ms) │ Customers task         │ 250.22        ║
║                       │ Other customer process │ 30.24         ║
╚═══════════════════════ Total: 365.92 ms ═══════╧═══════════════╝
```

#### 🧙 Tip

If your application has any logging system, it would be a perfect place to send the output. 
```php 
if (app()->environment('local')) {
    Log::debug(timeWarden()->output());
}
```

### Ways of using TimeWarden
You can use TimeWarden either with the aliases `timeWarden()` (or `timewarden()`):
```php
timeWarden()->task('Task 1')->start();
```

or by directly invoking the static methods of the `TimeWarden` facade:

```php
TimeWarden::task('Task 1')->start();
```
You decide how to use it 🙂

## **🧱 Architecture**
TimeWarden is composed of several types of elements. Below are some features of each of these elements.

### `TimeWarden`

`Tomloprod\TimeWarden\Support\Facades\TimeWarden` is a facade that acts as a simplified interface for using the rest of the TimeWarden elements.

#### Methods
Most methods in this class return their own instance, allowing fluent syntax through method chaining.

```php
// Destroys the TimeWarden instance and returns a new one.
TimeWarden::reset(): TimeWarden

// Creates a new group.
TimeWarden::group(string $groupName): TimeWarden

// Creates a new task inside the last created group 
// or within the TimeWarden instance itself.
TimeWarden::task(string $taskName): TimeWarden

// Starts the last created task
TimeWarden::start(): TimeWarden

// Stops the last created task
TimeWarden::stop(): TimeWarden

// Obtains all the created groups
TimeWarden::getGroups(): array

// Returns a table with execution time debugging info 
// (ideal for displaying in the console).
TimeWarden::output(): string
```
Additionally, it has all the methods of the [Taskable](#taskable) interface.

### `Task`
All tasks you create are instances of `Tomloprod\TimeWarden\Task`.
The most useful methods and properties of a task are the following:

#### Properties
- `name`

#### Methods
```php
$task = new Task('Task 1');

$task->start(): void
$task->stop(?callable $fn = null): void

// Returns the duration of the task in a human-readable format. Example: *1day 10h 20min 30sec 150ms*
$task->getFriendlyDuration(): string
// Returns the duration of the task in milliseconds
$task->getDuration(): float

// Returns the taskable element to which the task belongs.
$task->getTaskable(): ?Taskable

$task->hasStarted(): bool
$task->hasEnded(): bool

$task->getStartDateTime(): ?DateTimeImmutable
$task->getEndDateTime(): ?DateTimeImmutable

$task->getStartTimestamp(): float
$task->getEndTimestamp(): float

// Reactive execution time methods
$task->onExceedsMilliseconds(float $milliseconds, callable $fn): ?Task
$task->onExceedsSeconds(float $seconds, callable $fn): ?Task
$task->onExceedsMinutes(float $minutes, callable $fn): ?Task
$task->onExceedsHours(float $hours, callable $fn): ?Task
```

### `Group`
All groups you create are instances of the `Tomloprod\TimeWarden\Group` object.
The most useful methods and properties of a group are the following:

#### Properties
- `name`

#### Methods
```php

// Starts the last created task inside this group
$group->start(): void
```
Additionally, it has all the methods of the [Taskable](#taskable) interface.

### `Taskable`
`Tomloprod\TimeWarden\Contracts\Taskable` is the interface used by the **TimeWarden** instance as well as by each task **group**

#### Methods
```php
// Create a new task within the taskable.
$taskable->createTask(string $taskName): Task;

$taskable->getTasks(): array;

$taskable->getLastTask(): ?Task;

// Return the total time in milliseconds of all tasks within the taskable.
$taskable->getDuration(): float;
```

## **🚀 Installation & Requirements**

> **Requires [PHP 8.2+](https://php.net/releases/)**

You may use [Composer](https://getcomposer.org) to install TimeWarden into your PHP project:

```bash
composer require tomloprod/time-warden
```

## **🧑‍🤝‍🧑 Contributing**

Contributions are welcome, and are accepted via pull requests.
Please [review these guidelines](./CONTRIBUTING.md) before submitting any pull requests.

------

**TimeWarden** was created by **[Tomás López](https://twitter.com/tomloprod)** and open-sourced under the **[MIT license](https://opensource.org/licenses/MIT)**.
