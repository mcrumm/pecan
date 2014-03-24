# Pecan

Event-driven, non-blocking shell for ReactPHP.

Pecan (`pi-ˈkän`) provides a non-blocking alternative to the shell provided in the [Symfony Console](https://github.com/symfony/console) component.

## Shell

The Shell wraps a standard Console Application object.  It emits a `running` event on startup, `data` on input, and `exit` on close.

To output data via the Shell, emit an `output` event on the Shell, or use its `write()` method.

## Output

The Output classes extend the base Console Output. `StreamOutput` wraps a single stream resource, while `ConsoleOutput` contains both the `STDOUT` and `STDERR` streams.

## Usage

Here is a shell that echoes back any input it receives:

    $app = new \Symfony\Component\Console\Application();

    $shell = new \Pecan\Shell($app);

    $shell->on('data', function($line, \Pecan\Shell $shell) {
        $shell->write(sprintf('// in: %s', $line));
    });

    $loop = \React\EventLoop\Factory::create();

    $shell->start($loop);

    $loop->run();

Attaching to the `exit` event allows final output to be send before the shell exits:

    // Example callback for the exit event.
    $shell->on('exit', function($code, \Pecan\Shell $shell) {
        $shell->emit('output', [
            [
                'Goodbye.',
                sprintf('// Shell exits with code %d', $code)
            ],
            true
        ]);
    });