# Pecan

Event-driven, non-blocking shell for [ReactPHP](http://reactphp.org).

Pecan (`/pɪˈkɑːn/`) provides a non-blocking alternative to the shell provided in the [Symfony Console](https://github.com/symfony/console) component. Additionally, Pecan includes a basic, framework-agnostic shell component called `Drupe` that can be used as the basis for building custom shells.

## Shells

### Drupe

`Pecan\Drupe` is a standalone component for building event-driven console components.  I like to think of it as Pecan without the Shell.

Pass an `EventLoopInterface` to `start()` listening for input.

```
$loop  = \React\EventLoop\Factory::create();
$shell = new \Pecan\Drupe();

// $shell->start() returns $loop to allow this chaining.
$shell->start($loop)->run();
```

### Drupe Events

 - `running` - The shell is running.
 - `data` - Data was received from `STDIN`.
 - `error` - Indicates an I/O problem.
 - `close` - The shell was closed.

### Shell

`Pecan\Shell` extends `Drupe` to provide an event-driven wrapper for a standard `Symfony\Component\Console\Application`.  It can be used as a drop-in replacement for `Symfony\Component\Console\Shell`.

>**Note**: To maintain a Symfony Console-like workflow, calling `$shell->run()` on `Pecan\Shell` starts the EventLoop, so make sure it gets called last.

### Shell Events

`Shell` emits the same events as `Drupe`.

## Readline

`Pecan\Readline` provides an interface for reading line-by-line input from `STDIN`.  This component is heavily inspired by, and strives for parity with the [NodeJS Readline Component](http://nodejs.org/api/readline.html).

### Readline Events

 - `line` - A line has been read from `STDIN`.
 - `pause` - Reading from the stream has been paused.
 - `resume` - Reading from the stream has resumed.
 - `error` - The input stream encountered an error.
 - `close` - Then input stream was closed.

## Console

`Pecan\Console\Console` provides a standard interface for working with `STDOUT` and `STDERR`.  It is inspired heavily by the [NodeJS Console Component](http://nodejs.org/api/console.html) and takes some functionality from the [Symfony Console Component](http://symfony.com)

### Output

The Output classes extend the base Console Output. `StreamOutput` wraps a single stream resource, while `ConsoleOutput` contains both the `STDOUT` and `STDERR` streams.

- `StreamOutputInterface`
- `ConsoleOutputInterface`
- `PecanOutput`

## Using Pecan

### Using Drupe as a Standalone Shell

```php
use Pecan\Drupe;

$loop   = \React\EventLoop\Factory::create();
$shell  = new Drupe();

// Example one-time callback to write the initial prompt.
// This resumes reading from STDIN and kicks off the shell.
$shell->once('running', function (Drupe $shell) {
    $shell->setPrompt('drupe> ')->prompt();
});

// Example callback for the data event.
// By convention, any call to write() will be followed by a call to prompt() 
// once the data has been written to the output stream.
$shell->on('data', function ($line, Drupe $shell) {

    $command = (!$line && strlen($line) == 0) ? false : rtrim($line);

    if ('exit' === $command || false === $command) {
        $shell->close();
    } else {
        $shell->writeln(sprintf(PHP_EOL.'// in: %s', $line));
    }

});

// Example callback for the close event.
$shell->on('close', function ($code, Drupe $shell) {
    $shell->writeln([
        '// Goodbye.',
        sprintf('// Shell exits with code %d', $code),
    ]);
});

$shell->start($loop)->run();
```

### Using Shell with Symfony Console Applications

Here is a shell that echoes back any input it receives, and then exits.

```php
// Pecan\Shell wraps a standard Console Application.
use Symfony\Component\Console\Application;
use Pecan\Shell;

$shell = new Shell(new Application('pecan'));

$shell->on('data', function($line, Shell $shell) {
    $shell->write($line)->then(function($shell) {
        $shell->close();
    });
});

$shell->run();
```

### Injecting An `EventLoopInterface` into `Pecan\Shell`

Unless you pass `\Pecan\Shell` an object implementing `EventLoopInterface` as its second constructor method, the `Shell` will instantiate one from the EventLoop Factory.  Keep this in mind if you want to integrate Pecan into an existing React-based project.

```php
use Symfony\Component\Console\Application;
use Pecan\Shell;

$loop  = \React\EventLoop\Factory::create();

// Do other things requiring $loop...

$shell = new Shell(new Application('pecan'), $loop);

// We must still let the shell run the EventLoop.
$shell->run();
```
